import fs from 'fs';
import https from 'https';

const url = 'https://raw.githubusercontent.com/google/fonts/main/ofl/kanit/Kanit-Regular.ttf';

https.get(url, (res) => {
  if (res.statusCode !== 200) {
    console.error('Failed to download, status code:', res.statusCode);
    return;
  }
  const data = [];
  res.on('data', (chunk) => data.push(chunk));
  res.on('end', () => {
    const buffer = Buffer.concat(data);
    const base64 = buffer.toString('base64');
    const jsContent = `export const THSarabunNew = '${base64}';\n`;
    fs.writeFileSync('./resources/js/THSarabunNew.js', jsContent);
    console.log('Font downloaded and converted to Base64, size:', base64.length);
  });
}).on('error', (err) => {
  console.error('Error downloading font:', err);
});
