import fs from 'fs';
import mysql from 'mysql';
import dotenv from 'dotenv';

dotenv.config();

const conn = mysql.createConnection({
    'host': process.env.DB_HOST,
    'user': process.env.DB_USERNAME,
    'password': process.env.DB_PASSWORD,
    'database': process.env.DB_DATABASE,
});

function sliceIntoChunks(arr, chunkSize) {
    const res = [];
    for (let i = 0; i < arr.length; i += chunkSize) {
        const chunk = arr.slice(i, i + chunkSize);
        res.push(chunk);
    }
    return res;
}

export default function (productId, csv) {
    fs.readFile(csv, 'utf8', function (err, data) {
        const variantsQuantity = data.match(/_BISConfig.product.variants\[\d]\['inventory_quantity'] = \d.*;/g);

        if (Array.isArray(variantsQuantity)) {

            variantsQuantity.forEach(function (row) {
                const data = [...row.matchAll(/\d/g)];
                const variant = data[0];
                const quantity = data[1];

                conn.query('SELECT variant_id FROM variants where product_id = ?', [productId], function (err, results) {
                    if (err) throw err;
                    const variantId = results[variant];

                    conn.query('UPDATE historicals SET inventory_quantity = ? WHERE variant_id = ? and date_created = CURDATE()', [quantity, variantId], function (err, results) {
                        if (err) throw err;

                    });

                    conn.query('SELECT sales FROM historicals WHERE variant_id = ? AND date_created = SUBDATE(CURDATE(),1)', [variantId], function (err, results) {
                        if (err) throw err;

                    });
                })
            });
        }


    });
}
