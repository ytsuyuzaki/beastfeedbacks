import urllib.request
import urllib.parse
import json
import os

query = "\"rejecting complex array structures entirely or flattening them before deep parsing\""
url = f"https://api.duckduckgo.com/?q={urllib.parse.quote(query)}&format=json"

try:
    req = urllib.request.Request(
        url,
        data=None,
        headers={
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
    )
    with urllib.request.urlopen(req) as response:
        result = json.loads(response.read().decode())
        print(json.dumps(result, indent=2))
except Exception as e:
    print(f"Error: {e}")
