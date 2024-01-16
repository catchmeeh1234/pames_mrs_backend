const sql = require ("mssql/msnodesqlv8");

const config = {
  user: "sa",
  password: "p@$$w0rd",
  server: "WIN-JM1U4G08T87",
  port: 1433,
  driver: 'msnodesqlv8',
  database: "SmartMeterSystem",
  options: {
    enableArithAbort: true,
    trustedConnection: false
  },
  connectionTimeout: 150000,
  pool: {
    max: 10,
    min: 0,
    idleTimeoutMillis: 30000,
  },
};

const connection = new sql.ConnectionPool(config);

connection.connect(err => {
    if (err) {
        console.error(err);
        return;
    }

    console.log('Database connected.');

    const query = 'SELECT * FROM UserAccounts';

    const request = connection.request();

    request.query(query, (err, result) => {
        if (err) {
            console.error(err);
            return;
        }

        for (const row of result.recordset) {
          console.log(row.username);
        }

        connection.close();
    });
});

// sql.connect(config, function (err) {
//   if(err) {
//     console.log(err);
//   }
//   let request = new sql.Request();
//   request.query("SELECT * FROM UserAccounts",function(err, recordSet) {
//     if(err){
//       console.log(err);
//     }else {
//       // for (const result of recordSet) {
//       //   console.log(result.id);
//       // }
//       console.log(recordSet);
//     }
//   });
// });

