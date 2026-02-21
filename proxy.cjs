const http = require('http');
const httpProxy = require('http-proxy');

// Create the proxy server
const proxy = httpProxy.createProxyServer({});

// Error handling to prevent the proxy from crashing
proxy.on('error', (err, req, res) => {
    console.error('[Proxy Error]:', err);
    if (res.writeHead) {
        res.writeHead(500, { 'Content-Type': 'text/plain' });
        res.end('Proxy Error');
    }
});

const server = http.createServer((req, res) => {
    // Route WebSocket handshake or Reverb-specific paths to Reverb (8081)
    if (req.url.startsWith('/app') || req.headers.upgrade === 'websocket') {
        proxy.web(req, res, { target: 'http://127.0.0.1:8081' });
    } else {
        // Route everything else to Laravel (8000)
        proxy.web(req, res, { target: 'http://127.0.0.1:8000' });
    }
});

// Handle WebSocket upgrades
server.on('upgrade', (req, socket, head) => {
    console.log('[Proxy] Upgrading to WebSocket for:', req.url);
    proxy.ws(req, socket, head, { target: 'http://127.0.0.1:8081' });
});

const PORT = process.env.PORT || 8080;
server.listen(PORT, () => {
    console.log(`[Proxy] Listening on port ${PORT}`);
    console.log(`[Proxy] Forwarding Web to 8000 and WebSockets to 8081`);
});
