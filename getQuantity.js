require('dotenv').config();
const mysql = require('mysql');
const _ = require('lodash');
var Pool = require('phantomjs-pool').Pool;
var fs = require('fs');
const conn = mysql.createConnection({
    'host': process.env.DB_HOST,
    'user': process.env.DB_USERNAME,
    'password': process.env.DB_PASSWORD,
    'database': process.env.DB_DATABASE,
});


var productsArray = [];


function updateProductData(productId, siteId, quantity, sales) {
    conn.query(
        'update historicals set inventory_quantity = ?, sales = ? where date_created = CURDATE() and product_id = ? and site_id = ?',
        [quantity, sales, productId, siteId],
        (err, results, fields) => {
            if (err) {
                console.log(err);
                console.log('Error while updating data');
            }
        }
    );
}

function jobCallback(job, worker, index) {

    // as long as we have urls  we want to crawl we execute the job

    if (index < productsArray.length) {

        // the first argument contains the data which is passed to the worker
        // the second argument is a callback which is called when the job is executed
        job({
            url: productsArray[index].site,
            productId: productsArray[index].product_id,
            siteId: productsArray[index].site_id,
            id: index,
        }, function (err) {
            // Lets log if it worked
            if (err) {
                try {
                    fs.readFile(
                        __dirname + '/data/' + productsArray[index].product_id + '.csv',
                        'utf8',
                        function (err, data) {
                            if (err) {
                                console.log(err);

                            }

                            conn.query('select `regexp` from sites where id = ?', [productsArray[index].site_id], (err, results) => {
                                if (results.length) {
                                    const regexp = results[0].regexp;
                                    const re = new RegExp(regexp, 'g');
                                    const found = data.match(re);
                                    let quantity = found[0].trim();
                                    if (quantity.length === 0) {
                                        quantity = 0;
                                    }

                                    conn.query(
                                        'select IFNULL(inventory_quantity,0) as inventory_quantity from historicals where date_created = DATE_ADD(CURDATE(), INTERVAL -1 DAY) and product_id = ? and site_id = ? LIMIT 1',
                                        [productsArray[index].product_id, productsArray[index].site_id],
                                        (err, results, fields) => {

                                            if (results.length) {
                                                updateProductData(productsArray[index].product_id, productsArray[index].site_id, quantity, results.inventory_quantity - parseInt(quantity))
                                            } else {
                                                updateProductData(productsArray[index].product_id, productsArray[index].site_id, quantity, 0)
                                            }
                                        }
                                    );
                                    fs.unlinkSync(__dirname + '/data/' + productsArray[index].product_id + '.csv');


                                }
                            });


                        });
                } catch (err) {
                    console.log(err);
                }
                console.log('There were some problems for url ' + productsArray[index].site + ': ' + err.message);
            } else {
                console.log('DONE: ' + url + '(' + index + ')');
            }
        });
    } else {
        // if we have no more jobs, we call the function job with null
        job(null);
    }
}

var pool = new Pool({
    numWorkers: 1,
    jobCallback: jobCallback,
    workerFile: __dirname + '/worker.js',
    workerTimeout: 300000
});


conn.query("SELECT SUBSTRING_INDEX(GROUP_CONCAT(sites.url, catalogs.url, products.link),',',1) as site, products.product_id as product_id, sites.id as site_id FROM sites INNER JOIN catalogs on sites.id = catalogs.site_id INNER JOIN catalog_product on ((catalogs.catalog_id = catalog_product.catalog_id) and (catalogs.site_id = catalog_product.site_id))INNER JOIN products on ((products.product_id = catalog_product.product_id) and (products.site_id = catalog_product.site_id)) INNER JOIN historicals on((products.product_id = historicals.product_id) and (sites.id = historicals.site_id)) WHERE date_created = CURDATE() AND historicals.inventory_quantity IS NULL and catalogs.active = 'true' and products.active = 'true' group by catalogs.id, products.product_id", (err, results, fields) => {


    const productsChecked = [];
    for (let i = 0; i < results.length; i++) {
        if (!productsChecked.includes(results[i].product_id)) {
            productsArray.push(results[i]);
        }
        productsChecked.push(results[i].product_id);
    }
    pool.start();

});



