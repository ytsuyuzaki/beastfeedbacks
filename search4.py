import urllib.request
import urllib.parse
import json

query = "rejecting complex array structures entirely or flattening them before deep parsing"
url = f"https://html.duckduckgo.com/html/?q={urllib.parse.quote(query)}"

try:
    req = urllib.request.Request(
        url,
        headers={'User-Agent': 'Mozilla/5.0'}
    )
    with urllib.request.urlopen(req) as response:
        html = response.read().decode()
        import re
        snippets = re.findall(r'<a class="result__snippet[^>]*>(.*?)</a>', html, re.IGNORECASE | re.DOTALL)
        for s in snippets:
            print(re.sub(r'<[^>]+>', '', s).strip())
except Exception as e:
    print(f"Error: {e}")
