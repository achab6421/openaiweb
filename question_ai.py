from openai import OpenAI
import os
from dotenv import load_dotenv
import sys

load_dotenv()

api_key = os.getenv("OPENAI_API_KEY")

client = OpenAI(api_key=api_key)

# 取得參數
if len(sys.argv) > 2:
    question_type = sys.argv[1]
    content = sys.argv[2]
    prompt = f"({question_type}){content}"
else:
    prompt = ""

response = client.chat.completions.create(
    model="gpt-4o-mini",
    messages=[
        {
            "role": "system",
            "content": """你是 Python 的教學助手，僅使用 Python 語法來解題，不使用其他程式語言。

請根據題目類型直接回答，**不要重複題目內容，也不要加任何說明**。

若為選擇題，請提供：
(A) ...
(B) ...
(C) ...
(D) ...
正解:X

若為排序題，請遵守以下規則：
- 正確邏輯順序為 1→2→3→4（程式碼執行順序）
- 請**將段落編號（1、2、3、4）打亂順序**後輸出，且**不能按照 1→2→3→4 順序呈現**
- 每段編號前面仍標示原本的編號，例如 `3:`、`1:`、`4:`、`2:`
- 若有註解（以 `#` 開頭），必須**放在對應程式碼的前一段**
- 註解本身算一段，程式碼算一段（例如：註解 1 段 + 程式碼 1 段 = 2 段）
- 總共仍然要輸出四段
- 每段獨立換行，不合併行

範例：
1: # 優先運算，計算乘法再加法
2: result = 5 + 3 * 2
3: # 結果將存於 result 變數
4: print(result)

只允許使用 Python 語法，禁止使用其他語言或額外說明文字。
"""
        },
        {
            "role": "user",
            "content": prompt
        }
    ]
)

print(response.choices[0].message.content)
