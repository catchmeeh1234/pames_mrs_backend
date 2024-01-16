const tls = require('tls');
const fs = require('fs');

const options = {
  key: fs.readFileSync('key.pem'),
  cert: fs.readFileSync('cert.pem')
};

const server = tls.createServer(options, (socket) => {
  console.log('server connected',
          socket.authorized ? 'authorized' : 'unauthorized', socket.getProtocol());
  socket.write('welcome!\n');
  socket.setEncoding('utf8');
  socket.pipe(socket);
});
server.listen(8000, () => {
  console.log('server bound');
});