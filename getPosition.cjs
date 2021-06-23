require('dotenv').config();
const mysql = require('mysql');
const _ = require('lodash');
var Pool = require('phantomjs-pool').Pool;
var fs = require('fs');
var args = process.argv.slice(2);

const conn = mysql.createConnection({
    'host': process.env.DB_HOST,
    'user': process.env.DB_USERNAME,
    'password': process.env.DB_PASSWORD,
    'database': process.env.DB_DATABASE,
});

const positionUrl = [];

function jobCallback(job, worker, index) {

    // as long as we have urls  we want to crawl we execute the job

    if (index < positionUrl.length) {

        // the first argument contains the data which is passed to the worker
        // the second argument is a callback which is called when the job is executed
        job(
            {
                id: index,
                url: positionUrl[index].url,
                hostname: positionUrl[index].hostname

            }, async function (err) {
                // Lets log if it worked
                if (err) throw err;

                try {
                    async function importModule() {
                        return await import('./js-workers/position/' + positionUrl[index].hostname + '.mjs' );
                    }

                    const module = await importModule();
                    try {
                        module.default('data/position/' + positionUrl[index].hostname + '.csv').then((res) => {

                            process.exit();
                        });
                    } catch (err) {
                        throw err;
                    }

                } catch (err) {

                    console.log(err);
                }

            });
    } else {
        // if we have no more jobs, we call the function job with null
        job(null);

    }
}

var pool = function (hostname) {
    return new Pool({
        numWorkers: 1,
        jobCallback: jobCallback,
        workerFile: __dirname + `/js-workers/phantom/position/${hostname}.js`,
        workerTimeout: 1200000
    });
}

if (args.length > 0) {

    conn.query("SELECT id,product_html FROM sites WHERE id = ?", [args[0]], (err, result, fields) => {

            const {hostname} = new URL(result[0].product_html);
            if (fs.existsSync('data/position/' + hostname + '.csv')) {
                fs.unlinkSync('data/position/' + hostname + '.csv')
            }


            positionUrl.push(
                {
                    siteId: result[0].id,
                    url: result[0].product_html,
                    hostname: hostname,

                }
            );


            pool(hostname).start();
        }
    )
    ;
} else {
    conn.query("SELECT id,product_html FROM sites", (err, result, fields) => {

        const {hostname} = new URL(result[0].product_html);
        if (fs.existsSync('data/position/' + hostname + '.csv')) {
            fs.unlinkSync('data/position/' + hostname + '.csv')
        }


        positionUrl.push(
            {
                siteId: result[0].id,
                url: result[0].product_html,
                hostname: hostname,

            }
        );

        pool.start();
    });
}





