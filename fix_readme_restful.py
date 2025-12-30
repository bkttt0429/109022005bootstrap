import os

file_path = r'd:\xampp\htdocs\109022005bootstrap\README.md'
with open(file_path, 'r', encoding='utf-16') as f:
    content = f.read()

# Find the development log section
target_header = "### 最近期更新"
verification_header = "### 驗證步驟"

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

if target_header in content:
    # If the restful section is already there but possibly corrupted, we might want to clean it.
    # But safer to check if it's there.
    if "RESTful API 重構" in content:
        # It's there, but user says it's not or it's garbled.
        # Let's try to remove existing one first if found between Recent Updates and Verification
        start_idx = content.find(target_header)
        end_idx = content.find(verification_header)
        
        if end_idx != -1:
            # Replace everything from start of recent updates to verification header
            # assuming recent updates is one block. 
            # Or just append it right after the header.
            
            # Let's find where the items start.
            header_end = content.find('\n', start_idx) + 1
            
            # If RESTful is already there, remove it.
            if "RESTful API 重構" in content:
                import re
                content = re.sub(r'- \*\*RESTful API 重構.*?\n(  - .*?\n)*', '', content, flags=re.DOTALL)
            
            # Now insert it at the top of recent updates
            new_content = content[:header_end] + restful_content + content[header_end:]
            
            with open(file_path, 'w', encoding='utf-16') as f:
                f.write(new_content)
            print("Successfully updated RESTful section in README.md")
        else:
            print("Could not find Verification section")
    else:
        # Just insert after Recent Updates
        start_idx = content.find(target_header)
        header_end = content.find('\n', start_idx) + 1
        new_content = content[:header_end] + restful_content + content[header_end:]
        with open(file_path, 'w', encoding='utf-16') as f:
            f.write(new_content)
        print("Successfully added RESTful section to README.md")
else:
    print("Recent Updates header not found")
