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

const data = [];

function jobCallback(job, worker, index) {

    // as long as we have urls  we want to crawl we execute the job

    if (index < data.length) {

        // the first argument contains the data which is passed to the worker
        // the second argument is a callback which is called when the job is executed
        job(
            {
                id: index,
                url: data[index].url,
                productId: data[index].productId,
                hostname: data[index].hostname

            }, function (err) {
                // Lets log if it worked

                if (err) throw err;

                try {

                    async function importModule() {
                        return await import('./js-workers/quantity/' + data[index].hostname + '.mjs' );
                    }

                    importModule().then(function (module) {

                        module.default(data[index].productId, 'data/quantity/' + data[index].hostname + data[index].productId + '.csv').then(() => {
                            fs.unlink('data/quantity/' + data[index].hostname + data[index].productId + '.csv', function (err) {
                                if (err) throw err;
                            });
                        });

                    });
                } catch (err) {
                    console.log(err);
                }
            }
        );
    } else {
        // if we have no more jobs, we call the function job with null
        job(null);
    }
}

//
var pool = new Pool({
    numWorkers: 5,
    jobCallback: jobCallback,
    workerFile: __dirname + '/js-workers/quantity.js',
    workerTimeout: 300000
});
if (args.length > 0) {
    conn.query("SELECT distinct CONCAT(REPLACE(product_json, '.json', ''), CONCAT('/', products.handle)) as url, products.product_id FROM sites INNER JOIN catalogs on sites.id = catalogs.site_id INNER JOIN catalog_product on catalogs.catalog_id = catalog_product.catalog_id INNER JOIN products on catalog_product.product_id = products.product_id  WHERE sites.id = ? AND products.position IS NOT NULL and products.status = 'ENABLED'", [args[0]], (err, results, fields) => {
        if (err) throw err;
        console.log(results);
        results.forEach(function (result) {
            const {hostname} = new URL(result.url);
            data.push({url: result.url, productId: result.product_id, hostname: hostname});
        })

        pool.start();
    });
} else {
    conn.query("SELECT distinct CONCAT(REPLACE(product_json, '.json', ''), CONCAT('/', products.handle)) as url, products.product_id FROM sites INNER JOIN catalogs on sites.id = catalogs.site_id INNER JOIN catalog_product on catalogs.catalog_id = catalog_product.catalog_id INNER JOIN products on catalog_product.product_id = products.product_id WHERE products.position IS NOT NULL and products.status = 'ENABLED'", (err, results, fields) => {
        if (err) throw err;
        console.log(results);
        results.forEach(function (result) {
            const {hostname} = new URL(result.url);
            data.push({url: result.url, productId: result.product_id, hostname: hostname});
        })

        pool.start();
    });
}
