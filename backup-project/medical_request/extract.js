const fs = require('fs');
const html = fs.readFileSync('index.php', 'utf8');
const scriptMatch = html.match(/<script>([\s\S]*?)<\/script>/g);
if (scriptMatch) {
    let combined = '';
    scriptMatch.forEach(s => {
        let code = s.replace('<script>', '').replace('</script>', '');
        if (code.includes('const STATUS_TH')) {
            fs.writeFileSync('test3.js', code);
        }
    });
}
