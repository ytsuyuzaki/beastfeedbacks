import urllib.request
import urllib.parse
import json

query = "\"rejecting complex array structures entirely or flattening them before deep parsing\""
url = f"https://api.duckduckgo.com/?q={urllib.parse.quote(query)}&format=json"

try:
    req = urllib.request.Request(
        url,
        headers={'User-Agent': 'Mozilla/5.0'}
    )
    with urllib.request.urlopen(req) as response:
        result = json.loads(response.read().decode())
        print(json.dumps(result, indent=2))
except Exception as e:
    print(f"Error: {e}")
