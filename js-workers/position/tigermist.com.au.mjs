import fs from 'fs';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import {JSDOM} from 'jsdom';

dotenv.config();

const conn = await mysql.createConnection({
    'host': process.env.DB_HOST,
    'user': process.env.DB_USERNAME,
    'password': process.env.DB_PASSWORD,
    'database': process.env.DB_DATABASE,
});

export default function (csv, siteId) {
    return new Promise(function (resolve, reject) {
        fs.readFile(csv, 'utf8', async function (err, data) {

            const dom = new JSDOM(data);
            const collections = dom.window.document.getElementsByClassName('size-options collection');
            for (let i = 0; i < collections.length; i++) {
                let variants = collections[i].getElementsByClassName('size-variants');
                let productQuantity = 0;
                for (let j = 0; j < variants.length; j++) {

                    const quantity = parseInt(variants[j].children[0].value);
                    const variantId = parseInt(variants[j].children[3].value);
                    productQuantity += quantity;
                    await conn.query('UPDATE historicals SET inventory_quantity = ? WHERE variant_id = ? and date_created = CURDATE() and site_id = ?', [quantity, variantId, siteId]);
                    const [rows, fields] = await conn.query('SELECT inventory_quantity FROM historicals WHERE variant_id = ? and date_created = SUBDATE(CURDATE(),1) and site_id = ?', [variantId, siteId]);
                    if (rows.length > 0) {
                        console.log(rows);
                        if (rows[0].inventory_quantity != null) {
                            await conn.query('UPDATE historicals SET sales = ? WHERE variant_id = ? and date_created = CURDATE() and site_id = ?', [rows[0].inventory_quantity - quantity, variantId, siteId]);
                        } else {
                            await conn.query('UPDATE historicals SET sales = ? WHERE variant_id = ? and date_created = CURDATE() and site_id = ?', [0, variantId, siteId]);
                        }
                    }
                }
                if (variants.length > 0) {
                    var [product, fields] = await conn.query('SELECT product_id FROM variants WHERE id = ? LIMIT 1', [parseInt(variants[0].children[3].value)]);
                    if (product.length > 0) {
                        await conn.query('UPDATE products SET position = ? WHERE id = ? and site_id = ?', [i + 1, product[0].product_id, siteId]);
                        await conn.query('UPDATE products SET quantity = ? WHERE id = ? and site_id = ?', [productQuantity, product[0].product_id, siteId]);
                        await conn.query('INSERT INTO product_position (product_id,position,date_created,site_id) VALUES (?,?, CURDATE(), ?) ON DUPLICATE KEY UPDATE position = VALUES(position)', [product[0].product_id, i + 1, siteId]);
                    }


                }
            }
            resolve('true');
        });
    });
}

