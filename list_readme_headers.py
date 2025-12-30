import os

file_path = r'd:\xampp\htdocs\109022005bootstrap\README.md'
with open(file_path, 'r', encoding='utf-16') as f:
    content = f.read()

import re
headers = re.findall(r'^### (.*)', content, re.MULTILINE)
print("Headers found:")
for h in headers:
    print(f"[{h.strip()}]")
