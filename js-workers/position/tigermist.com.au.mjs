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

export default function (csv) {
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
                    await conn.query('UPDATE historicals SET inventory_quantity = ? WHERE variant_id = ? and date_created = CURDATE()', [quantity, variantId]);
                    const [rows, fields] = await conn.query('SELECT inventory_quantity FROM historicals WHERE variant_id = ? and date_created = SUBDATE(CURDATE(),1)', [variantId]);
                    if (rows.length > 0) {
                        console.log(rows);
                        if (rows[0].inventory_quantity != null) {
                            await conn.query('UPDATE historicals SET sales = ? WHERE variant_id = ? and date_created = CURDATE()', [rows[0].inventory_quantity - quantity, variantId]);
                        } else {
                            await conn.query('UPDATE historicals SET sales = ? WHERE variant_id = ? and date_created = CURDATE()', [0, variantId]);
                        }
                    }
                }
                if (variants.length > 0) {
                    var [product, fields] = await conn.query('SELECT product_id FROM variants WHERE variant_id = ? LIMIT 1', [parseInt(variants[0].children[3].value)]);
                    if (product.length > 0) {
                        await conn.query('UPDATE products SET position = ? WHERE product_id = ?', [i + 1, product[0].product_id]);
                        await conn.query('UPDATE products SET quantity = ? WHERE product_id = ?', [productQuantity, product[0].product_id]);
                        await conn.query('INSERT INTO product_position (product_id,position,date_created) VALUES (?,?, CURDATE()) ON DUPLICATE KEY UPDATE position = VALUES(position)', [product[0].product_id, i + 1]);
                    }


                }
            }
            resolve('true');
        });
    });
}

