import urllib.request
import urllib.parse
import json
import os

query = "wordpress \"rejecting complex array structures entirely\" OR \"flattening them before deep parsing\""
# duckduckgo html search
url = f"https://html.duckduckgo.com/html/?q={urllib.parse.quote(query)}"

try:
    req = urllib.request.Request(
        url,
        data=None,
        headers={
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
    )
    with urllib.request.urlopen(req) as response:
        html = response.read().decode()
        # just extract text inside result snippets
        import re
        snippets = re.findall(r'<a class="result__snippet[^>]*>(.*?)</a>', html, re.IGNORECASE | re.DOTALL)
        for s in snippets:
            print(re.sub(r'<[^>]+>', '', s).strip())
except Exception as e:
    print(f"Error: {e}")
