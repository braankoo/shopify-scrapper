require('dotenv').config();
const phantom = require('phantom');
const mysql = require('mysql');
const _ = require('lodash');

const conn = mysql.createConnection({
    'host': process.env.DB_HOST,
    'user': process.env.DB_USERNAME,
    'password': process.env.DB_PASSWORD,
    'database': process.env.DB_DATABASE,
});


conn.connect({multipleStatements: true});

conn.query("SELECT SUBSTRING_INDEX(GROUP_CONCAT(sites.url, catalogs.url, products.link),',',1) as site, products.product_id as product_id, sites.id as site_id FROM sites INNER JOIN catalogs on sites.id = catalogs.site_id INNER JOIN catalog_product on ((catalogs.catalog_id = catalog_product.catalog_id) and (catalogs.site_id = catalog_product.site_id))INNER JOIN products on ((products.product_id = catalog_product.product_id) and (products.site_id = catalog_product.site_id)) INNER JOIN historicals on((products.product_id = historicals.product_id) and (sites.id = historicals.site_id)) WHERE date_created = CURDATE() AND historicals.inventory_quantity IS NULL and catalogs.active = 'true' and products.active = 'true' group by catalogs.id, products.product_id", (err, results, fields) => {
    if (err) {
        throw err
    }
    results.forEach((result) => {
        getProductSiteContent(result.site).then((resonse) => {
            console.log(resonse);
        })
    })


});


const getProductSiteContent = async (url) => {
    console.log(url);
    const instance = await phantom.create(['--ssl-protocol=any', '--ignore-ssl-errors=true']);
    const page = await instance.createPage();
    await page.open(`${url}`);

    const content = await page.property('content');
    const regex = /(?<=\[0\]\['inventory_quantity'\] =).\d*/g;
    const found = content.match(regex);
    console.log('------');
    console.log(found);
    console.log('------');
    await instance.exit();
    return content;
};





