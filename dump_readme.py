import os

file_path = r'd:\xampp\htdocs\109022005bootstrap\README.md'
with open(file_path, 'r', encoding='utf-16') as f:
    content = f.read()

with open('full_readme_dump.txt', 'w', encoding='utf-8') as f:
    f.write(content)
print("Dumped to full_readme_dump.txt")
