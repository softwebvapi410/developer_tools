// ═══════════════════════════════════════
//  CODE FORMATTER
// ═══════════════════════════════════════

let _cfLang = 'javascript';
let _cfLastOutput = '';

function cfOnLangChange() {
    _cfLang = document.getElementById('cfLang').value;
}

function cfOnInput() {
    const input = document.getElementById('cfInput');
    const lines = input.value.split('\n').length;
    const chars = input.value.length;
    document.getElementById('cfInputStats').textContent =
        `${lines.toLocaleString()} lines · ${chars.toLocaleString()} chars`;
}

function cfGetIndent() {
    const v = document.getElementById('cfIndent').value;
    if (v === 'tab') return '\t';
    return ' '.repeat(parseInt(v));
}

// ── Core format dispatcher ──────────────────────────────────────
function cfFormat() {
    const input = document.getElementById('cfInput').value.trim();
    if (!input) return;
    cfHideError();

    const lang = document.getElementById('cfLang').value;
    let result = '';

    try {
        switch (lang) {
            case 'json':    result = cfFormatJson(input);       break;
            case 'html':    result = cfFormatHtml(input);       break;
            case 'css':     result = cfFormatCss(input);        break;
            case 'javascript':
            case 'php':
            case 'python':  result = cfFormatJs(input);         break;
            case 'sql':     result = cfFormatSql(input);        break;
            case 'xml':     result = cfFormatXml(input);        break;
            case 'markdown':result = cfFormatMarkdown(input);   break;
            default:        result = cfFormatJs(input);
        }
        cfSetOutput(result, 'Formatted');
    } catch (e) {
        cfShowError('Format error: ' + e.message);
    }
}

function cfMinify() {
    const input = document.getElementById('cfInput').value.trim();
    if (!input) return;
    cfHideError();

    const lang = document.getElementById('cfLang').value;
    let result = '';

    try {
        switch (lang) {
            case 'json':
                result = JSON.stringify(JSON.parse(input));
                break;
            case 'css':
                result = input
                    .replace(/\/\*[\s\S]*?\*\//g, '')
                    .replace(/\s*([{}:;,>~+])\s*/g, '$1')
                    .replace(/\s+/g, ' ')
                    .replace(/;\}/g, '}')
                    .trim();
                break;
            case 'html':
                result = input
                    .replace(/<!--[\s\S]*?-->/g, '')
                    .replace(/\s+/g, ' ')
                    .replace(/>\s+</g, '><')
                    .trim();
                break;
            case 'sql':
                result = input.replace(/\s+/g, ' ').trim();
                break;
            default:
                // JS/generic: strip comments and collapse whitespace
                result = input
                    .replace(/\/\/[^\n]*/g, '')
                    .replace(/\/\*[\s\S]*?\*\//g, '')
                    .replace(/\n\s*\n/g, '\n')
                    .replace(/[ \t]+/g, ' ')
                    .trim();
        }
        cfSetOutput(result, 'Minified');
    } catch (e) {
        cfShowError('Minify error: ' + e.message);
    }
}

// ── JSON formatter ──────────────────────────────────────────────
function cfFormatJson(input) {
    const parsed = JSON.parse(input); // throws if invalid — caught above
    return JSON.stringify(parsed, null, cfGetIndent());
}

// ── XML formatter ───────────────────────────────────────────────
function cfFormatXml(input) {
    const indent = cfGetIndent();
    let result = '';
    let pad = 0;

    // Strip existing whitespace between tags
    input = input.replace(/>\s+</g, '><').trim();

    input.split(/(<[^>]+>)/).forEach(token => {
        if (!token.trim()) return;

        if (token.match(/^<\/[^>]+>$/)) {
            // Closing tag
            pad--;
            result += indent.repeat(Math.max(0, pad)) + token + '\n';
        } else if (token.match(/^<[^/][^>]*(\/?)>$/) && token.endsWith('/>')) {
            // Self-closing
            result += indent.repeat(pad) + token + '\n';
        } else if (token.match(/^<[^/?!][^>]*>$/)) {
            // Opening tag
            result += indent.repeat(pad) + token + '\n';
            pad++;
        } else if (token.match(/^<[?!]/)) {
            result += indent.repeat(pad) + token + '\n';
        } else {
            // Text node
            const text = token.trim();
            if (text) result += indent.repeat(pad) + text + '\n';
        }
    });

    return result.trim();
}

// ── HTML formatter ──────────────────────────────────────────────
function cfFormatHtml(input) {
    const indent = cfGetIndent();
    const voidTags = new Set(['area','base','br','col','embed','hr','img','input','link','meta','param','source','track','wbr']);
    const inlineTags = new Set(['a','abbr','b','bdi','bdo','cite','code','data','dfn','em','i','kbd','mark','q','rp','rt','ruby','s','samp','small','span','strong','sub','sup','time','u','var','wbr']);
    const preTags = new Set(['pre','script','style','textarea']);

    let result = '';
    let depth = 0;
    let inPre = false;

    // Simple tokenizer
    const tokens = input.match(/<!--[\s\S]*?-->|<[^>]+>|[^<]+/g) || [];

    tokens.forEach(token => {
        const trimmed = token.trim();
        if (!trimmed) return;

        if (token.startsWith('<!--')) {
            result += indent.repeat(depth) + token.trim() + '\n';
            return;
        }

        const tagMatch = token.match(/^<\/?([a-zA-Z][a-zA-Z0-9-]*)/);
        if (!tagMatch) {
            if (!inPre) {
                const text = trimmed.replace(/\s+/g, ' ');
                if (text) result += indent.repeat(depth) + text + '\n';
            } else {
                result += token;
            }
            return;
        }

        const tagName = tagMatch[1].toLowerCase();
        const isClose = token.startsWith('</');
        const isSelf = token.endsWith('/>') || voidTags.has(tagName);

        if (inPre) {
            result += token;
            if (isClose && preTags.has(tagName)) { inPre = false; result += '\n'; }
            return;
        }

        if (isClose) {
            depth = Math.max(0, depth - 1);
            result += indent.repeat(depth) + token.trim() + '\n';
            return;
        }

        result += indent.repeat(depth) + token.trim() + '\n';

        if (!isSelf) {
            if (preTags.has(tagName)) { inPre = true; }
            else { depth++; }
        }
    });

    return result.trim();
}

// ── CSS formatter ───────────────────────────────────────────────
function cfFormatCss(input) {
    const indent = cfGetIndent();
    let result = '';
    let depth = 0;
    let i = 0;

    // Tokenize into: { } ; comment text
    const tokens = input.match(/\/\*[\s\S]*?\*\/|[{};\n]|[^{};\n]+/g) || [];

    tokens.forEach(tok => {
        const t = tok.trim();
        if (!t) return;

        if (t.startsWith('/*')) {
            result += indent.repeat(depth) + t + '\n';
        } else if (t === '{') {
            result = result.trimEnd() + ' {\n';
            depth++;
        } else if (t === '}') {
            depth = Math.max(0, depth - 1);
            result += indent.repeat(depth) + '}\n\n';
        } else if (t === ';') {
            result = result.trimEnd() + ';\n';
        } else {
            result += indent.repeat(depth) + t;
        }
    });

    return result.trim();
}

// ── SQL formatter ───────────────────────────────────────────────
function cfFormatSql(input) {
    const indent = cfGetIndent();
    const keywords = [
        'SELECT','FROM','WHERE','AND','OR','NOT','IN','IS','NULL','LIKE',
        'JOIN','LEFT JOIN','RIGHT JOIN','INNER JOIN','OUTER JOIN','FULL JOIN','CROSS JOIN',
        'ON','AS','GROUP BY','ORDER BY','HAVING','LIMIT','OFFSET','UNION','UNION ALL',
        'INSERT INTO','VALUES','UPDATE','SET','DELETE FROM','CREATE TABLE','ALTER TABLE',
        'DROP TABLE','INDEX','PRIMARY KEY','FOREIGN KEY','REFERENCES','CONSTRAINT',
        'DISTINCT','COUNT','SUM','AVG','MIN','MAX','CASE','WHEN','THEN','ELSE','END',
        'WITH','RETURNS','BEGIN','END','DECLARE','EXEC','EXECUTE','PROCEDURE'
    ];

    // Sort by length desc so longer phrases match first
    keywords.sort((a, b) => b.length - a.length);

    let sql = input
        .replace(/\s+/g, ' ')
        .trim();

    // Insert newlines before top-level keywords
    const topLevel = ['SELECT','FROM','WHERE','GROUP BY','ORDER BY','HAVING','LIMIT',
                      'OFFSET','UNION','UNION ALL','INSERT INTO','VALUES','UPDATE','SET',
                      'DELETE FROM','JOIN','LEFT JOIN','RIGHT JOIN','INNER JOIN','OUTER JOIN',
                      'WITH'];

    let result = sql;
    topLevel.forEach(kw => {
        const re = new RegExp('\\b' + kw.replace(/ /g, '\\s+') + '\\b', 'gi');
        result = result.replace(re, '\n' + kw);
    });

    // Uppercase all keywords
    keywords.forEach(kw => {
        const re = new RegExp('\\b' + kw.replace(/ /g, '\\s+') + '\\b', 'gi');
        result = result.replace(re, kw);
    });

    // Indent sub-clauses (AND, OR inside WHERE)
    result = result
        .split('\n')
        .map(line => {
            const t = line.trim();
            if (/^(AND|OR)\b/i.test(t)) return indent + indent + t;
            if (/^(SELECT|FROM|WHERE|GROUP BY|ORDER BY|HAVING|LIMIT|OFFSET|UNION|INSERT INTO|VALUES|UPDATE|SET|DELETE FROM|JOIN|LEFT JOIN|RIGHT JOIN|INNER JOIN|WITH)\b/i.test(t))
                return t;
            return indent + t;
        })
        .join('\n');

    return result.trim();
}

// ── JS / generic formatter ──────────────────────────────────────
function cfFormatJs(input) {
    // Collapse excess blank lines and fix common indentation issues
    const indent = cfGetIndent();
    const lines = input.split('\n');
    let depth = 0;
    let result = [];
    let inString = false;
    let inMultiComment = false;

    lines.forEach(rawLine => {
        const line = rawLine.trim();
        if (!line) { result.push(''); return; }

        // Track multi-line comments
        if (!inMultiComment && line.startsWith('/*')) inMultiComment = true;
        if (inMultiComment) {
            result.push(indent.repeat(depth) + line);
            if (line.includes('*/')) inMultiComment = false;
            return;
        }

        // Count braces to determine depth
        let opens = (line.match(/[{(]/g) || []).length;
        let closes = (line.match(/[})]/g) || []).length;

        // Decrease before closing braces
        if (/^[})\]]/.test(line)) depth = Math.max(0, depth - 1);

        result.push(indent.repeat(depth) + line);

        // Net depth change (but skip lines that are just closing)
        if (/^[})\]]/.test(line)) {
            depth += Math.max(0, opens - closes);
        } else {
            depth = Math.max(0, depth + opens - closes);
        }
    });

    // Remove more than 2 consecutive blank lines
    return result
        .join('\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

// ── Markdown formatter ──────────────────────────────────────────
function cfFormatMarkdown(input) {
    return input
        .replace(/\r\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .replace(/[ \t]+$/gm, '')
        .trim();
}

// ── Output helpers ──────────────────────────────────────────────
function cfSetOutput(text, label) {
    _cfLastOutput = text;
    document.getElementById('cfOutput').value = text;
    const lines = text.split('\n').length;
    const chars = text.length;
    document.getElementById('cfOutputStats').textContent =
        `${lines.toLocaleString()} lines · ${chars.toLocaleString()} chars`;
    const badge = document.getElementById('cfStatusBadge');
    badge.textContent = label;
    badge.className = 'tag tag-good';
    badge.style.display = 'inline-block';
}

function cfShowError(msg) {
    const el = document.getElementById('cfError');
    document.getElementById('cfErrorMsg').textContent = msg;
    el.style.display = 'flex';
    const badge = document.getElementById('cfStatusBadge');
    badge.textContent = 'Error';
    badge.className = 'tag tag-issue';
    badge.style.display = 'inline-block';
    refreshIcons();
}

function cfHideError() {
    document.getElementById('cfError').style.display = 'none';
}

// ── Copy, Download, Paste, Upload ──────────────────────────────
function cfCopyOutput() {
    const text = document.getElementById('cfOutput').value;
    if (!text) return;
    const btn = document.getElementById('cfCopyBtn');
    copyText(text, btn);
}

function cfDownload() {
    const text = document.getElementById('cfOutput').value || document.getElementById('cfInput').value;
    if (!text) return;
    const lang = document.getElementById('cfLang').value;
    const ext = { json:'json', html:'html', css:'css', javascript:'js', php:'php',
                  python:'py', sql:'sql', xml:'xml', markdown:'md' }[lang] || 'txt';
    const blob = new Blob([text], { type: 'text/plain' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `formatted.${ext}`;
    a.click();
    URL.revokeObjectURL(a.href);
}

async function cfPaste() {
    try {
        const text = await navigator.clipboard.readText();
        document.getElementById('cfInput').value = text;
        cfOnInput();
    } catch(e) {
        document.getElementById('cfInput').focus();
    }
}

function cfUpload() { document.getElementById('cfFileInput').click(); }

function cfLoadFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    const ext = file.name.split('.').pop().toLowerCase();
    const langMap = { js:'javascript', json:'json', html:'html', css:'css',
                      sql:'sql', xml:'xml', php:'php', py:'python', md:'markdown' };
    if (langMap[ext]) {
        document.getElementById('cfLang').value = langMap[ext];
        _cfLang = langMap[ext];
    }
    const reader = new FileReader();
    reader.onload = ev => {
        document.getElementById('cfInput').value = ev.target.result;
        cfOnInput();
    };
    reader.readAsText(file);
    e.target.value = '';
}

function cfClear() {
    document.getElementById('cfInput').value = '';
    document.getElementById('cfOutput').value = '';
    document.getElementById('cfInputStats').textContent = '0 lines · 0 chars';
    document.getElementById('cfOutputStats').textContent = '';
    document.getElementById('cfStatusBadge').style.display = 'none';
    cfHideError();
    _cfLastOutput = '';
}

// ── Quick examples ──────────────────────────────────────────────
const CF_EXAMPLES = {
    json: `{"name":"DEV Toolz Pro","version":"2.0","features":["crawl","dns","whois","sitemap"],"settings":{"concurrency":4,"timeout":15,"followRedirects":true},"author":{"name":"Dev Team","email":"dev@example.com"}}`,
    html: `<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Example</title><link rel="stylesheet" href="style.css"></head><body><header><nav><ul><li><a href="/">Home</a></li><li><a href="/about">About</a></li></ul></nav></header><main><h1>Hello World</h1><p>This is a <strong>formatted</strong> HTML document.</p></main><footer><p>&copy; 2025</p></footer></body></html>`,
    css: `.container{max-width:1200px;margin:0 auto;padding:0 20px}.header{background:linear-gradient(135deg,#3b5bdb,#4f46e5);color:#fff;padding:20px 0}.nav{display:flex;gap:16px;align-items:center}.nav a{color:rgba(255,255,255,.8);text-decoration:none;font-weight:600;transition:color .2s}.nav a:hover{color:#fff}.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:12px;border:none;cursor:pointer;font-weight:700;transition:all .2s}.btn-primary{background:#3b5bdb;color:#fff}.btn-primary:hover{background:#2f4ac0}`,
    javascript: `const SEOAuditor={config:{concurrency:4,timeout:15,maxDepth:3},async crawl(url){const results=[];const queue=[url];const visited=new Set();while(queue.length&&results.length<100){const batch=queue.splice(0,this.config.concurrency);const promises=batch.filter(u=>!visited.has(u)).map(async u=>{visited.add(u);try{const res=await fetch('?action=crawl&url='+encodeURIComponent(u));const data=await res.json();results.push(data);if(data.links)queue.push(...data.links);}catch(e){console.error('Crawl failed:',u,e);}});await Promise.all(promises);}return results;},formatScore(score){if(score>=80)return{label:'Good',color:'#059669'};if(score>=50)return{label:'Fair',color:'#d97706'};return{label:'Poor',color:'#dc2626'};}};`,
    sql: `SELECT u.id,u.name,u.email,COUNT(o.id) AS order_count,SUM(o.total) AS total_spent FROM users u LEFT JOIN orders o ON o.user_id=u.id WHERE u.created_at>='2024-01-01' AND u.status='active' AND (o.status='completed' OR o.status='shipped') GROUP BY u.id,u.name,u.email HAVING COUNT(o.id)>0 ORDER BY total_spent DESC LIMIT 50 OFFSET 0;`,
    xml: `<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/</loc><lastmod>2025-01-01</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url><url><loc>https://example.com/about</loc><lastmod>2025-01-01</lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url></urlset>`,
};

function cfLoadExample(lang) {
    document.getElementById('cfLang').value = lang;
    _cfLang = lang;
    document.getElementById('cfInput').value = CF_EXAMPLES[lang] || '';
    cfOnInput();
    cfFormat();
}

// Responsive grid: collapse to 1-col on mobile
(function cfResponsive() {
    const check = () => {
        const grid = document.getElementById('cfGrid');
        if (grid) grid.style.gridTemplateColumns = window.innerWidth < 640 ? '1fr' : '1fr 1fr';
    };
    window.addEventListener('resize', check);
    document.addEventListener('DOMContentLoaded', check);
})();
