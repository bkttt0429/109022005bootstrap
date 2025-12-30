import os

file_path = r'd:\xampp\htdocs\109022005bootstrap\README.md'
with open(file_path, 'r', encoding='utf-16') as f:
    content = f.read()

# Find the index of the restful section
idx = content.find('RESTful API 重構')
if idx != -1:
    print(f"Index: {idx}")
    print(content[idx:idx+1000])
else:
    print("RESTful section NOT FOUND")

# Also find Verification steps
vidx = content.find('Verification Steps')
if vidx != -1:
    print(f"Verification index: {vidx}")
