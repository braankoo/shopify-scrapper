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

export default function (productId, csv) {
    fs.readFile(csv, 'utf8', function (err, data) {
        const variantsQuantity = data.match(/_BISConfig.product.variants\[\d]\['inventory_quantity'] = \d.*;/g);
        variantsQuantity.forEach(function (row) {
            const data = [...row.matchAll(/\d/g)];
            const variant = data[0];
            const quantity = data[1];

            conn.query('SELECT variant_id FROM variants where product_id = ?', [productId], function (err, results) {
                if (err) throw err;
                const variantId = results[variant].variant_id;

                conn.query('UPDATE historicals SET inventory_quantity = ? WHERE variant_id = ? and date_created = CURDATE()', [quantity, variantId], function (err, results) {
                    if (err) throw err;
                });
            })
        });


    });
}
