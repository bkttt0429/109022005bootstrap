import os

file_path = r'd:\xampp\htdocs\109022005bootstrap\README.md'
with open(file_path, 'r', encoding='utf-16') as f:
    content = f.read()

restful_content = """- **RESTful API 重構與 CORS 優化**:
  - **CORS 核心修正**:
    - 全面啟用 `Access-Control-Allow-Origin` 動態來源偵測。
    - 支援 `Access-Control-Allow-Credentials: true` 以允許 Session Cookie 跨域傳遞。
    - 補完 `Access-Control-Allow-Headers`，新增 `Authorization` 支援 JWT 驗證。
    - 修正 `OPTIONS` Preflight 請求的正確響應 (HTTP 200)。
  - **Google 第三方登入強化**:
    - 重構 `auth_google.php`，修正 SSL 憑證驗證問題 (Dev 環境)。
    - 登入成功後同步回傳 JWT Token，確保與原生登入機制的 Session 狀態一致。
    - 當 `auth_google` 成功驗證後，前端會自動存儲 Token 並重整 Context。
  - **API 標準化**:
    - 統一 `auth_api.php`, `register_api.php`, `checkout_api.php` 的 Header 設定，確保跨域行為一致。
"""

# Try to find "驗證步驟"
anchor = "### 驗證步驟"

if anchor in content:
    # Check if we already have it to avoid duplicates
    if "RESTful API 重構" in content:
        import re
        # Remove existing one regardless of corruption
        content = re.sub(r'- \*\*RESTful API 重構.*?\n(  - .*?\n)*', '', content, flags=re.DOTALL)
    
    parts = content.split(anchor)
    # Insert before anchor
    new_content = parts[0] + restful_content + "\n" + anchor + parts[1]
    
    with open(file_path, 'w', encoding='utf-16') as f:
        f.write(new_content)
    print("Successfully updated README.md")
else:
    # Just append at bottom if no anchor
    with open(file_path, 'a', encoding='utf-16') as f:
        f.write("\n\n" + restful_content)
    print("Appended to end of README.md")
