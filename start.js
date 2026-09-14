#!/usr/bin/env node

/**
 * DrinkFlow Unified Service Orchestrator
 * Starts Backend (Laravel), Frontend (Vite), and Realtime Gateway (Socket.IO) concurrently.
 */

const { spawn } = require('child_process');
const path = require('path');

const rootDir = __dirname;
const srcDir = path.join(rootDir, 'src');
const realtimeDir = path.join(rootDir, 'realtime');

const colors = {
    reset: '\x1b[0m',
    cyan: '\x1b[36m',
    magenta: '\x1b[35m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
    bold: '\x1b[1m'
};

const isWin = process.platform === 'win32';
const npmCmd = isWin ? 'npm.cmd' : 'npm';
const phpCmd = 'php';

const services = [
    {
        name: 'Backend',
        color: colors.cyan,
        cmd: phpCmd,
        args: ['artisan', 'serve', '--port', '8080'],
        cwd: srcDir
    },
    {
        name: 'Frontend',
        color: colors.magenta,
        cmd: npmCmd,
        args: ['run', 'dev'],
        cwd: srcDir
    },
    {
        name: 'Realtime',
        color: colors.green,
        cmd: npmCmd,
        args: ['start'],
        cwd: realtimeDir
    }
];

console.log(`${colors.bold}${colors.yellow}=====================================================================${colors.reset}`);
console.log(`${colors.bold}${colors.yellow}               DRINKFLOW APPLICATION ORCHESTRATOR                    ${colors.reset}`);
console.log(`${colors.bold}${colors.yellow}=====================================================================${colors.reset}`);
console.log(`Starting all 3 DrinkFlow services concurrently...\n`);

const children = [];

services.forEach(service => {
    const prefix = `${service.color}${colors.bold}[${service.name}]${colors.reset} `;
    console.log(`${prefix}Spawning: ${service.cmd} ${service.args.join(' ')} (in ${path.relative(rootDir, service.cwd)})`);

    const child = spawn(service.cmd, service.args, {
        cwd: service.cwd,
        env: { ...process.env, FORCE_COLOR: '1' },
        shell: isWin
    });

    child.stdout.on('data', data => {
        const lines = data.toString().split('\n');
        lines.forEach(line => {
            if (line.trim().length > 0) {
                console.log(`${prefix}${line}`);
            }
        });
    });

    child.stderr.on('data', data => {
        const lines = data.toString().split('\n');
        lines.forEach(line => {
            if (line.trim().length > 0) {
                console.error(`${prefix}${colors.red}${line}${colors.reset}`);
            }
        });
    });

    child.on('close', code => {
        console.log(`${prefix}${colors.yellow}Process exited with code ${code}${colors.reset}`);
    });

    children.push(child);
});

function cleanup() {
    console.log(`\n${colors.yellow}Gracefully stopping all services...${colors.reset}`);
    children.forEach(child => {
        try {
            if (isWin) {
                spawn('taskkill', ['/pid', child.pid, '/f', '/t']);
            } else {
                child.kill('SIGINT');
            }
        } catch (e) {
            // ignore
        }
    });
    process.exit(0);
}

process.on('SIGINT', cleanup);
process.on('SIGTERM', cleanup);
