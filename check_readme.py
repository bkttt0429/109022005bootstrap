import os

file_path = r'd:\xampp\htdocs\109022005bootstrap\README.md'
encodings = ['utf-16', 'utf-8', 'cp950']
for enc in encodings:
    try:
        with open(file_path, 'r', encoding=enc) as f:
            content = f.read()
            if 'RESTful API' in content:
                print(f"FOUND in {enc}")
            else:
                print(f"NOT FOUND in {enc}")
            # print first 1000 chars to see what's going on
            print("--- START ---")
            print(content[:500])
            print("--- END ---")
            break
    except:
        continue
