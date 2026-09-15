import crypto from 'node:crypto';
import fs from 'node:fs';
import { createServer } from 'node:http';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { Server } from 'socket.io';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

function loadEnvFile(filePath) {
  if (!fs.existsSync(filePath)) return;
  try {
    const content = fs.readFileSync(filePath, 'utf8');
    for (const line of content.split(/\r?\n/)) {
      const trimmed = line.trim();
      if (!trimmed || trimmed.startsWith('#')) continue;
      const match = trimmed.match(/^([A-Za-z0-9_]+)\s*=\s*(.*)$/);
      if (match) {
        const key = match[1];
        let val = match[2].trim();
        if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
          val = val.slice(1, -1);
        }
        if (!process.env[key]) {
          process.env[key] = val;
        }
      }
    }
  } catch (err) {
    console.warn(`Could not load ${filePath}:`, err.message);
  }
}

// Automatically load from realtime/.env or ../src/.env
loadEnvFile(path.join(__dirname, '.env'));
loadEnvFile(path.join(__dirname, '../src/.env'));

const port = Number(process.env.PORT || 3001);
const secret = process.env.SOCKET_TOKEN_SECRET || process.env.APP_KEY || '';
const internalSecret = process.env.REALTIME_INTERNAL_SECRET || '';
if (!secret) throw new Error('SOCKET_TOKEN_SECRET or APP_KEY is required (check .env in realtime/ or src/)');

const decode = value => Buffer.from(value.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - value.length % 4) % 4), 'base64').toString('utf8');
const verify = token => {
  if (typeof token !== 'string') return null;
  const [encoded, signature] = token.split('.', 2);
  if (!encoded || !signature) return null;
  const expected = crypto.createHmac('sha256', secret).update(encoded).digest('hex');
  if (signature.length !== expected.length || !crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected))) return null;
  try {
    const payload = JSON.parse(decode(encoded));
    return ['user', 'admin', 'superadmin'].includes(payload.actor_type) && Number(payload.exp) >= Math.floor(Date.now() / 1000) ? payload : null;
  } catch { return null; }
};

let authenticationFailures = 0;
const recentDisconnects = [];
const httpServer = createServer((req, res) => {
  if (req.method === 'POST' && req.url === '/internal/emit') {
    if (!internalSecret || req.headers['x-realtime-secret'] !== internalSecret) {
      res.writeHead(401, { 'Content-Type': 'application/json' });
      return res.end(JSON.stringify({ error: 'unauthorized' }));
    }
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
      try {
        const input = JSON.parse(body || '{}');
        const allowedEvents = new Set([
          'order.created', 'order.updated', 'order.deleted',
          'campaign.created', 'campaign.updated', 'campaign.deleted', 'campaign.closed',
          'campaign.menu.updated', 'campaign.menu.deleted', 'campaign.participant.declined', 'notification.created'
        ]);
        const roomId = Number(input.room_id);
        const requiresRoom = input.event !== 'notification.created';
        if (!allowedEvents.has(input.event) || !Number.isInteger(roomId) || (requiresRoom && roomId < 1) || (!requiresRoom && roomId < 0)) {
          res.writeHead(422, { 'Content-Type': 'application/json' });
          return res.end(JSON.stringify({ error: 'invalid_event' }));
        }
        const payload = input.payload && typeof input.payload === 'object' ? input.payload : {};
        io.to(`room:${roomId}`).emit(input.event, payload);
        if (typeof input.user_channel === 'string' && /^(?:user|global_user):\d+$/.test(input.user_channel)) {
          io.to(input.user_channel).emit(input.event, payload);
        }
        res.writeHead(202, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ delivered: true }));
      } catch {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        return res.end(JSON.stringify({ error: 'invalid_json' }));
      }
    });
    return;
  }
  if (req.url !== '/health') {
    res.writeHead(404);
    return res.end();
  }
  const connectionsByRoom = {};
  let connectedUsers = 0;
  let connectedAdmins = 0;
  let connectedSuperadmins = 0;
  for (const socket of io?.of('/').sockets.values() || []) {
    const claims = socket.data.claims || {};
    if (claims.actor_type === 'user') connectedUsers += 1;
    if (claims.actor_type === 'admin') connectedAdmins += 1;
    if (claims.actor_type === 'superadmin') connectedSuperadmins += 1;
    for (const roomId of claims.room_ids || (claims.room_id ? [claims.room_id] : [])) connectionsByRoom[roomId] = (connectionsByRoom[roomId] || 0) + 1;
  }
  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ connected_users: connectedUsers, connected_admins: connectedAdmins, connected_superadmins: connectedSuperadmins, connections_by_room: connectionsByRoom, recent_disconnects: recentDisconnects, authentication_failures: authenticationFailures }));
});
const io = new Server(httpServer, { cors: { origin: process.env.CORS_ORIGIN || '*', credentials: true } });

io.use((socket, next) => {
  const claims = verify(socket.handshake.auth?.token || socket.handshake.headers.authorization?.replace(/^Bearer\s+/i, ''));
  if (!claims) { authenticationFailures += 1; return next(new Error('Invalid or expired socket token')); }
  socket.data.claims = claims;
  next();
});

io.on('connection', socket => {
  const claims = socket.data.claims;
  const allowed = new Set();
  if (claims.actor_type === 'user' && Number.isInteger(Number(claims.room_user_id))) allowed.add(`user:${claims.room_user_id}`);
  if (claims.actor_type === 'user' && Number.isInteger(Number(claims.global_user_id))) allowed.add(`global_user:${claims.global_user_id}`);
  if (claims.actor_type === 'admin') allowed.add(`admin:${claims.admin_id}`);
  if (claims.actor_type === 'superadmin') { allowed.add('superadmin'); allowed.add('system'); }
  for (const roomId of claims.room_ids || (claims.room_id ? [claims.room_id] : [])) allowed.add(`room:${roomId}`);
  for (const channel of allowed) socket.join(channel);
  socket.on('disconnect', reason => { recentDisconnects.unshift({ actor_type: claims.actor_type, reason, at: new Date().toISOString() }); recentDisconnects.splice(20); });
  socket.on('subscribe', channel => {
    if (allowed.has(channel)) socket.join(channel);
    else socket.emit('error', { message: 'Channel is not authorized' });
  });
});

httpServer.listen(port, () => console.log(`DrinkFlow realtime listening on ${port}`));
