import os

file_path = r'd:\xampp\htdocs\109022005bootstrap\README.md'
with open(file_path, 'r', encoding='utf-16') as f:
    content = f.read()

# Update production environment URL to point to the dist folder
old_line = "- **生產環境**: 存取 `http://localhost/109022005bootstrap/main_app/` (由 XAMPP 託管)"
new_line = "- **生產環境**: 存取 `http://localhost/109022005bootstrap/main_app/client/dist/` (由 XAMPP 託管)"

# Since I don't know the exact old line content (it might be different), 
# I will use regex to find and replace any URL in that section.
import re
new_content = re.sub(
    r'- \*\*生產環境\*\*: 存取 `http://localhost/109022005bootstrap/.*?`',
    r'- **生產環境**: 存取 `http://localhost/109022005bootstrap/main_app/client/dist/`',
    content
)

if new_content != content:
    with open(file_path, 'w', encoding='utf-16') as f:
        f.write(new_content)
    print("Successfully updated production URL in README.md")
else:
    # Try a fallback if the regex didn't match (maybe no bold or different text)
    new_content = re.sub(
        r'生產環境.*?:.*?http://localhost/109022005bootstrap/\S*',
        r'生產環境: 存取 `http://localhost/109022005bootstrap/main_app/client/dist/`',
        content
    )
    if new_content != content:
        with open(file_path, 'w', encoding='utf-16') as f:
            f.write(new_content)
        print("Successfully updated production URL (fallback) in README.md")
    else:
        print("Could not find the production URL line to update.")

