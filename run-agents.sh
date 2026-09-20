#!/bin/bash
# =============================================================================
# run-agents.sh - Orchestrator + 3 agent chạy song song (DB / Logic / UI)
#
# CLI chính: Claude (claude -p). Fallback: codex, agy.
# Kết quả chỉ là ĐỀ XUẤT (proposal) lưu trong .antigravity/staging/ - script
# không tự sửa source code. Agent chỉ được phép đọc (Read/Glob/Grep) và đọc
# MCP memory; không có quyền Edit/Write.
#
# Cách dùng:
#   ./run-agents.sh "<mô tả yêu cầu>"
#
# Biến môi trường (tùy chọn):
#   AI_CLI=claude|codex|agy  Ép dùng một CLI (mặc định tự dò: claude > codex > agy)
#   CLAUDE_MODEL=<model>     Model cho Claude CLI (mặc định: theo cấu hình của claude)
#   CODEX_MODEL=<model>      Model cho Codex CLI  (mặc định: theo ~/.codex/config.toml)
#   AGENT_TIMEOUT=<giây>     Timeout cho mỗi lần gọi AI (mặc định 300)
# =============================================================================

# Dừng script nếu có lỗi xảy ra (pipefail bắt lỗi trong pipeline)
set -euo pipefail

print_usage() {
  echo "Cách dùng:"
  echo "   ./run-agents.sh \"<mô tả yêu cầu của bạn>\""
  echo "Biến môi trường tùy chọn: AI_CLI, CLAUDE_MODEL, CODEX_MODEL, AGENT_TIMEOUT"
}

# ─────────────────────────────────────────────
# GUARD: Kiểm tra argument bắt buộc
# ─────────────────────────────────────────────
case "${1:-}" in
  "")
    echo "❌ Thiếu yêu cầu."
    print_usage
    exit 1
    ;;
  -h|--help)
    print_usage
    exit 0
    ;;
esac

USER_REQUEST="$1"
PREFERRED_CLI="${AI_CLI:-}"
AGENT_TIMEOUT="${AGENT_TIMEOUT:-300}"

# ─────────────────────────────────────────────
# KHỞI TẠO THƯ MỤC VÀ FILE
# ─────────────────────────────────────────────
WORK_DIR=".antigravity"
TASK_FILE="$WORK_DIR/current_task.json"
LOG_FILE="$WORK_DIR/agent.log"
STAGING_DIR="$WORK_DIR/staging"

# Đường dẫn tuyệt đối của repo (Git Bash trên Windows: C:/...; Linux/macOS: pwd)
ABS_ROOT="$(pwd -W 2>/dev/null || pwd)"

mkdir -p "$WORK_DIR" "$STAGING_DIR"
: > "$LOG_FILE"  # Reset log mỗi lần chạy
rm -f "$WORK_DIR"/db_output.txt "$WORK_DIR"/logic_output.txt "$WORK_DIR"/ui_output.txt

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Run-agents started" >> "$LOG_FILE"
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Request: $USER_REQUEST" >> "$LOG_FILE"

# ─────────────────────────────────────────────
# GUARD: Kiểm tra jq
# ─────────────────────────────────────────────
if ! command -v jq &>/dev/null; then
  echo "❌ Công cụ 'jq' chưa được cài đặt hoặc không có trong PATH." | tee -a "$LOG_FILE"
  echo "   Cài đặt: https://jqlang.github.io/jq/download/" | tee -a "$LOG_FILE"
  exit 1
fi

# ─────────────────────────────────────────────
# AUTO-DETECT: AI CLI tool
# Ưu tiên: claude > codex > agy (hoặc ép bằng AI_CLI=...)
# ─────────────────────────────────────────────
CODEX_CMD=()

cli_available() {
  case "$1" in
    claude|agy)
      command -v "$1" &>/dev/null
      ;;
    codex)
      if command -v codex &>/dev/null; then
        CODEX_CMD=(codex)
      elif npx --no-install codex --version &>/dev/null; then
        CODEX_CMD=(npx --no-install codex)
      else
        return 1
      fi
      ;;
    *)
      return 1
      ;;
  esac
}

AI_KIND=""
if [ -n "$PREFERRED_CLI" ]; then
  if cli_available "$PREFERRED_CLI"; then
    AI_KIND="$PREFERRED_CLI"
  fi
else
  for candidate in claude codex agy; do
    if cli_available "$candidate"; then
      AI_KIND="$candidate"
      break
    fi
  done
fi

if [ -z "$AI_KIND" ]; then
  if [ -n "$PREFERRED_CLI" ]; then
    echo "❌ AI_CLI='$PREFERRED_CLI' không khả dụng (chưa cài, không có trong PATH, hoặc không thuộc claude / codex / agy)." | tee -a "$LOG_FILE"
  else
    echo "❌ Không tìm thấy AI CLI tool (claude / codex / agy)." | tee -a "$LOG_FILE"
  fi
  echo "   Cài Claude CLI: npm install -g @anthropic-ai/claude-code" | tee -a "$LOG_FILE"
  exit 1
fi
echo "[INFO] Sử dụng AI CLI: $AI_KIND" | tee -a "$LOG_FILE"

# ─────────────────────────────────────────────
# MCP CONFIG
# Sinh file config lúc chạy với MEMORY_FILE_PATH là đường dẫn TUYỆT ĐỐI, vì
# @modelcontextprotocol/server-memory resolve đường dẫn tương đối theo thư mục
# của package (không phải thư mục dự án). File nguồn: .agents/mcp_config.json
# ─────────────────────────────────────────────
MCP_CONFIG=".agents/mcp_config.json"
GENERATED_MCP="$WORK_DIR/mcp_config.generated.json"
MCP_READY=0

if [ -f "$MCP_CONFIG" ]; then
  if jq --arg root "$ABS_ROOT" '
        (.mcpServers[]? | select(.env.MEMORY_FILE_PATH? != null) | .env.MEMORY_FILE_PATH)
        |= (if (test("^[A-Za-z]:") or startswith("/") or startswith("~"))
            then . else $root + "/" + . end)
      ' "$MCP_CONFIG" > "$GENERATED_MCP" 2>>"$LOG_FILE"; then
    MCP_READY=1
    echo "[INFO] MCP config: $MCP_CONFIG -> $GENERATED_MCP (đường dẫn memory tuyệt đối)" | tee -a "$LOG_FILE"
    MEMORY_ABS="$(jq -r '.mcpServers[]?.env.MEMORY_FILE_PATH? // empty' "$GENERATED_MCP" | head -n 1)"
    if [ -n "$MEMORY_ABS" ] && [ ! -f "$MEMORY_ABS" ]; then
      echo "[WARN] Chưa có file memory: $MEMORY_ABS - agents sẽ thấy knowledge graph rỗng." | tee -a "$LOG_FILE"
    fi
  else
    echo "[WARN] Không đọc được $MCP_CONFIG (JSON lỗi?) - agents sẽ chạy không có MCP Memory." | tee -a "$LOG_FILE"
  fi
else
  echo "[WARN] MCP config không tìm thấy tại $MCP_CONFIG - agents sẽ chạy không có MCP Memory." | tee -a "$LOG_FILE"
fi

# ─────────────────────────────────────────────
# CẤU HÌNH RIÊNG CHO TỪNG CLI
# ─────────────────────────────────────────────
CLAUDE_ARGS=(-p)
CODEX_ARGS=(--sandbox read-only)

if [ "$AI_KIND" = "claude" ]; then
  # Chỉ cho phép công cụ đọc + các tool ĐỌC của MCP memory (tên server: "memory").
  # Không có Edit/Write/Bash -> agent không thể sửa file hay ghi đè memory.
  CLAUDE_TOOLS="Read,Glob,Grep"
  if [ -n "${CLAUDE_MODEL:-}" ]; then
    CLAUDE_ARGS+=(--model "$CLAUDE_MODEL")
  fi
  if [ "$MCP_READY" -eq 1 ]; then
    CLAUDE_ARGS+=(--mcp-config "$GENERATED_MCP" --strict-mcp-config)
    CLAUDE_TOOLS="$CLAUDE_TOOLS,mcp__memory__read_graph,mcp__memory__search_nodes,mcp__memory__open_nodes"
  fi
  CLAUDE_ARGS+=(--allowedTools "$CLAUDE_TOOLS")
fi

if [ "$AI_KIND" = "codex" ]; then
  if [ -n "${CODEX_MODEL:-}" ]; then
    CODEX_ARGS+=(--model "$CODEX_MODEL")
  fi
  # Codex đọc MCP từ .codex/config.toml (project trusted) hoặc ~/.codex/config.toml,
  # KHÔNG đọc codex.json. Sinh .codex/config.toml từ config đã có đường dẫn tuyệt đối.
  if [ "$MCP_READY" -eq 1 ]; then
    CODEX_TOML=".codex/config.toml"
    CODEX_MARKER="# GENERATED by run-agents.sh"
    if [ ! -f "$CODEX_TOML" ] || head -n 1 "$CODEX_TOML" | grep -q "^$CODEX_MARKER"; then
      mkdir -p .codex
      {
        echo "$CODEX_MARKER - đừng sửa tay, sửa .agents/mcp_config.json"
        jq -r '
          .mcpServers | to_entries[] |
          "[mcp_servers.\(.key)]\ncommand = \(.value.command | @json)\nargs = \(.value.args // [] | @json)\n"
          + (if .value.env then
               "[mcp_servers.\(.key).env]\n"
               + (.value.env | to_entries | map("\(.key) = \(.value | @json)") | join("\n")) + "\n"
             else "" end)
        ' "$GENERATED_MCP"
      } > "$CODEX_TOML"
      echo "[INFO] Đã sinh $CODEX_TOML cho Codex (project cần được Codex 'trust')." | tee -a "$LOG_FILE"
    else
      echo "[WARN] $CODEX_TOML đã tồn tại và không do script sinh - giữ nguyên, không ghi đè." | tee -a "$LOG_FILE"
    fi
  fi
fi

# ─────────────────────────────────────────────
# Helper: gọi AI CLI với prompt và output file
# Trả về exit code của CLI (124 = quá thời gian AGENT_TIMEOUT)
# ─────────────────────────────────────────────
run_with_timeout() {
  if command -v timeout &>/dev/null; then
    timeout "$AGENT_TIMEOUT" "$@"
  else
    "$@"
  fi
}

call_ai() {
  local PROMPT="$1"
  local OUT_FILE="$2"
  local RC=0

  case "$AI_KIND" in
    claude)
      # Prompt qua stdin: tránh lỗi tham số variadic (--allowedTools/--mcp-config)
      # nuốt prompt và tránh giới hạn độ dài dòng lệnh trên Windows.
      printf '%s' "$PROMPT" | run_with_timeout claude "${CLAUDE_ARGS[@]}" > "$OUT_FILE" 2>>"$LOG_FILE" || RC=$?
      ;;
    codex)
      run_with_timeout "${CODEX_CMD[@]}" exec "${CODEX_ARGS[@]}" "$PROMPT" > "$OUT_FILE" 2>>"$LOG_FILE" || RC=$?
      ;;
    agy)
      run_with_timeout agy run "$PROMPT" > "$OUT_FILE" 2>>"$LOG_FILE" || RC=$?
      ;;
  esac

  if [ "$RC" -ne 0 ]; then
    {
      echo "[ERROR] $AI_KIND thoát với mã $RC. Output:"
      head -c 2000 "$OUT_FILE" 2>/dev/null || true
      echo ""
    } >> "$LOG_FILE"
    if [ "$RC" -eq 124 ]; then
      echo "   ⏱️  Quá thời gian ${AGENT_TIMEOUT}s. Tăng bằng: AGENT_TIMEOUT=600 ./run-agents.sh ..." | tee -a "$LOG_FILE"
    fi
    if grep -qiE "not logged in|/login|invalid api key|authentication" "$OUT_FILE" 2>/dev/null; then
      echo "   🔑 Claude CLI chưa đăng nhập: chạy 'claude' rồi gõ /login (hoặc đặt biến ANTHROPIC_API_KEY)." | tee -a "$LOG_FILE"
    fi
  fi
  return "$RC"
}

# Lấy đúng object JSON từ output của AI (bỏ code fence / lời dẫn) rồi chuẩn hóa key.
# Thử từ dòng '{' đầu tiên tới từng dòng kết thúc bằng '}' (từ trên xuống) cho
# tới khi jq parse được -> không bị nhầm với lời dẫn phía sau JSON.
extract_json() {
  local FILE="$1"
  local TMP="$1.tmp"
  local START="" END=""

  if ! jq -e 'type == "object"' "$FILE" &>/dev/null; then
    START=$(grep -n -m1 -E '^[[:space:]]*\{' "$FILE" | cut -d: -f1 || true)
    if [ -n "$START" ]; then
      for END in $(grep -n -E '\}[[:space:]]*$' "$FILE" | cut -d: -f1 || true); do
        if [ "$END" -ge "$START" ] \
          && sed -n "${START},${END}p" "$FILE" > "$TMP" \
          && jq -e 'type == "object"' "$TMP" &>/dev/null; then
          mv "$TMP" "$FILE"
          break
        fi
      done
    fi
    rm -f "$TMP"
  fi

  if jq -e 'type == "object"' "$FILE" &>/dev/null; then
    jq '{db_task: (.db_task // null), logic_task: (.logic_task // null), ui_task: (.ui_task // null), target_files: (.target_files // [])}' \
      "$FILE" > "$TMP" && mv "$TMP" "$FILE"
    return 0
  fi
  return 1
}

# ─────────────────────────────────────────────
# PROMPT DÙNG CHUNG
# ─────────────────────────────────────────────
read -r -d '' PROJECT_CONTEXT <<'PROMPT_EOF' || true
Project: DrinkFlow - a Laravel 12 (PHP >= 8.2) application located in the 'src/' subfolder of the repository (src/app, src/routes, src/resources/{views,js,css}, src/lang/{vi,en,ja}, src/database/migrations).
Database: PostgreSQL 16 ONLY (never MySQL, never Supabase). Never compare a bigint id column with a string slug/code.
Follow AGENTS.md and .agents/rules/*.md. Every file path you output MUST be relative to the repository root and start with 'src/'.
Data sources: MCP memory (read-only tools open_nodes / search_nodes / read_graph) has the entities 'Project_Layout', 'Database_Schema_Summary', 'DrinkFlow_Architecture_Rules' and one entity per table. You may also use Read/Glob/Grep to inspect existing files. You cannot and must not modify files: your output is a proposal only.
PROMPT_EOF

read -r -d '' OUTPUT_FORMAT <<'PROMPT_EOF' || true
Output format: for EVERY file output one header line '=== FILE: <path relative to repo root> ===' followed by a fenced code block with the COMPLETE new content of that file (or a unified diff for a small edit to a large file). No other prose.
PROMPT_EOF

read -r -d '' DB_RULES <<'PROMPT_EOF' || true
- Output ONLY Migration or Model PHP code.
- Use declare(strict_types=1), full type hints, PHPDoc on every method.
- Do NOT touch Controllers, Actions, Views, or Routes.
- Use Enum classes (App\Enums\*) for status columns, no magic strings.
- Primary keys: id() / bigIncrements. Enforce invariants with unique indexes / foreign keys at DB level.
PROMPT_EOF

read -r -d '' LOGIC_RULES <<'PROMPT_EOF' || true
- Output ONLY Action classes, Service classes, FormRequests, or Controller/route changes.
- Keep controllers thin (Controller -> FormRequest -> Action/Service -> Model) and put them in the correct actor namespace (see AGENTS.md).
- Use declare(strict_types=1), full type hints, PHPDoc on every method.
- Do NOT touch Migrations or Models.
- Use Enum classes (App\Enums\*) for status, no magic strings.
- Add lang keys to ALL of src/lang/vi, src/lang/en, src/lang/ja when adding validation messages or UI text.
PROMPT_EOF

read -r -d '' UI_RULES <<'PROMPT_EOF' || true
- Output ONLY Blade view, JS, CSS, or lang-file code (src/resources/views, src/resources/js, src/resources/css, src/lang/{vi,en,ja}).
- Use the layout components required by AGENTS.md (no duplicated <html>/<head>/header/footer markup).
- All <img> tags MUST have loading="lazy", meaningful alt text and an onerror fallback.
- All UI text MUST use __('file.key') or @lang('file.key') - no hardcoded strings; every new key must exist in vi, en AND ja.
- Extract inline <script> to src/resources/js/ and inline <style> to src/resources/css/.
- Do NOT touch PHP backend files (Controllers, Models, Migrations, Actions, Services).
PROMPT_EOF

echo "================================================="
echo "🚀 1. KÍCH HOẠT ORCHESTRATOR AGENT (Lập kế hoạch)"
echo "================================================="

# Tạo prompt Orchestrator
ORCHESTRATOR_PROMPT="Role: Laravel System Architect.
$PROJECT_CONTEXT

Task: Analyze the user request below and generate a structured execution plan.

Constraints:
- First open the memory entities 'Project_Layout' and 'Database_Schema_Summary' (open_nodes) to learn the project layout and the existing schema; open individual table entities when relevant.
- To locate affected routes/views/controllers use Glob/Grep (e.g. src/routes/*.php, src/resources/views/**). DO NOT read whole PHP source files.
- Return ONLY a valid raw JSON object (no markdown, no explanation, no code fences).
- JSON must have exactly these keys:
  {
    \"db_task\": \"<migration/model task or null>\",
    \"logic_task\": \"<action/service/controller/formrequest task or null>\",
    \"ui_task\": \"<blade/view/css/js/lang task or null>\",
    \"target_files\": [\"<path relative to repo root, starting with src/>\"]
  }

User Request:
$USER_REQUEST"

# Gọi Orchestrator
echo "[INFO] Gọi Orchestrator..." | tee -a "$LOG_FILE"
if ! call_ai "$ORCHESTRATOR_PROMPT" "$TASK_FILE"; then
  echo "❌ Orchestrator thất bại. Xem log: $LOG_FILE"
  exit 1
fi

# Bóc JSON khỏi output (bỏ ```json ... ``` hoặc lời dẫn) và chuẩn hóa key
if ! extract_json "$TASK_FILE"; then
  echo "❌ Orchestrator không trả về JSON hợp lệ. Nội dung nhận được:" | tee -a "$LOG_FILE"
  cat "$TASK_FILE"
  echo ""
  echo "   Xem log chi tiết: $LOG_FILE"
  exit 1
fi

echo "✅ Kế hoạch đã tạo tại $TASK_FILE"
jq . "$TASK_FILE"
echo ""

# ─────────────────────────────────────────────
# AGENT RUNNER
# run_agent <nhãn> <key trong task json> <role> <rules> <output file>
# Return 0 = xong/bỏ qua vì không có task; 1 = lỗi
# ─────────────────────────────────────────────
run_agent() {
  local LABEL="$1" KEY="$2" ROLE="$3" RULES="$4" OUT_FILE="$5"
  local TASK TARGET PROMPT RC=0

  echo "  [$LABEL] Đang khởi chạy..." | tee -a "$LOG_FILE"

  TASK=$(jq -r --arg k "$KEY" '.[$k] // empty | if type == "string" then . else tojson end' "$TASK_FILE" 2>/dev/null || true)
  TARGET=$(jq -r '(.target_files // []) | map(tostring) | join(", ")' "$TASK_FILE" 2>/dev/null || true)

  if [ -z "$TASK" ] || [ "$TASK" = "null" ]; then
    echo "  [$LABEL] ⏭️  Không có nhiệm vụ, bỏ qua." | tee -a "$LOG_FILE"
    return 0
  fi

  PROMPT="Role: $ROLE.
$PROJECT_CONTEXT

Original user request:
$USER_REQUEST

Your task ($LABEL):
$TASK

Target files (relative to repo root, may be incomplete): $TARGET

Instructions:
$RULES

$OUTPUT_FORMAT"

  call_ai "$PROMPT" "$OUT_FILE" || RC=$?
  if [ "$RC" -ne 0 ]; then
    echo "  [$LABEL] ⚠️  Kết thúc với lỗi (exit $RC). Xem $LOG_FILE" | tee -a "$LOG_FILE"
    return 1
  fi
  if [ ! -s "$OUT_FILE" ]; then
    echo "  [$LABEL] ⚠️  Output rỗng. Xem $LOG_FILE" | tee -a "$LOG_FILE"
    return 1
  fi
  echo "  [$LABEL] ✅ Hoàn thành!" | tee -a "$LOG_FILE"
  return 0
}

# ─────────────────────────────────────────────
# CHẠY AGENTS SONG SONG
# ─────────────────────────────────────────────
echo "================================================="
echo "⚡ 2. KÍCH HOẠT MULTI-AGENTS CHẠY SONG SONG"
echo "================================================="

run_agent "DB Agent" db_task "Laravel Database Specialist" "$DB_RULES" "$WORK_DIR/db_output.txt" &
PID_DB=$!

run_agent "Logic Agent" logic_task "Laravel Feature Developer (Action/Service pattern)" "$LOGIC_RULES" "$WORK_DIR/logic_output.txt" &
PID_LOGIC=$!

run_agent "UI Agent" ui_task "Laravel Frontend Developer (Blade + Vite, Alpine.js or vanilla JS)" "$UI_RULES" "$WORK_DIR/ui_output.txt" &
PID_UI=$!

# Chờ và capture exit codes
DB_EXIT=0
LOGIC_EXIT=0
UI_EXIT=0

wait "$PID_DB"    || DB_EXIT=$?
wait "$PID_LOGIC" || LOGIC_EXIT=$?
wait "$PID_UI"    || UI_EXIT=$?

OVERALL_STATUS=0
if [ "$DB_EXIT" -ne 0 ];    then echo "⚠️  DB Agent exit: $DB_EXIT"       | tee -a "$LOG_FILE"; OVERALL_STATUS=1; fi
if [ "$LOGIC_EXIT" -ne 0 ]; then echo "⚠️  Logic Agent exit: $LOGIC_EXIT" | tee -a "$LOG_FILE"; OVERALL_STATUS=1; fi
if [ "$UI_EXIT" -ne 0 ];    then echo "⚠️  UI Agent exit: $UI_EXIT"       | tee -a "$LOG_FILE"; OVERALL_STATUS=1; fi

# ─────────────────────────────────────────────
# TỔNG HỢP VÀ LƯU STAGING (chưa áp dụng vào source)
# ─────────────────────────────────────────────
echo "================================================="
echo "🎉 3. TỔNG HỢP KẾT QUẢ (đề xuất - chưa áp dụng vào source)"
echo "================================================="

TIMESTAMP=$(date +%Y%m%d_%H%M%S)

for agent in db logic ui; do
  OUTPUT_FILE="$WORK_DIR/${agent}_output.txt"
  if [ -f "$OUTPUT_FILE" ] && [ -s "$OUTPUT_FILE" ]; then
    STAGING_FILE="$STAGING_DIR/${agent}_${TIMESTAMP}.txt"
    cp "$OUTPUT_FILE" "$STAGING_FILE"
    echo ""
    echo "─── Code từ $(echo "$agent" | tr '[:lower:]' '[:upper:]') Agent (lưu tại $STAGING_FILE) ───"
    cat "$OUTPUT_FILE"
    rm -f "$OUTPUT_FILE"
  fi
done

# Giữ lại task.json để tham khảo
cp "$TASK_FILE" "$STAGING_DIR/task_${TIMESTAMP}.json" 2>/dev/null || true

echo ""
echo "[$(date '+%Y-%m-%d %H:%M:%S')] Run-agents finished (status: $OVERALL_STATUS)" >> "$LOG_FILE"

if [ "$OVERALL_STATUS" -eq 0 ]; then
  echo "✅ Tiến trình hoàn tất! Đề xuất đã lưu tại: $STAGING_DIR/ (hãy review rồi áp dụng thủ công)"
else
  echo "⚠️  Tiến trình hoàn tất với một số cảnh báo. Xem log: $LOG_FILE"
fi

exit "$OVERALL_STATUS"
