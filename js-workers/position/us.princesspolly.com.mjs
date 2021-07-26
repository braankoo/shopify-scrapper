import fs from 'fs';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

const conn = await mysql.createConnection({
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

export default function (csv, siteId) {
    return new Promise(function (resolve, reject) {
        fs.readFile(csv, 'utf8', async function (err, data) {

            let variants = Array.from(
                new Set(data.match(/data-product-selected-variant=".\d*/g)
                    .map(function (variant) {
                        return variant.replace(/\D/g, '');
                    })
                ));

            const chunks = sliceIntoChunks(variants, 100);

            for (const chunk of chunks) {

                await (async () => {
                    const [rows, fields] = await conn.query('SELECT product_id,variant_id FROM variants WHERE variant_id IN (?)', [chunk]);

                    const variantsWithPosition = rows.map(function (row) {
                        row.position = variants.findIndex(function (variant) {
                            return variant == row.variant_id;
                        });
                        return row;
                    });

                    const productsWithPositions = variantsWithPosition.map(function (variant) {
                        const obj = {};
                        obj.product_id = variant.product_id;
                        obj.position = variant.position + 1;
                        return obj;
                    });
                    for (const product of productsWithPositions) {

                        await conn.query('UPDATE products SET position = ? WHERE product_id = ? and site_id = ?', [product.position, product.product_id, siteId]);
                        await conn.query('INSERT INTO product_position (product_id,position,date_created,site_id) VALUES (?,?, CURDATE(),?) ON DUPLICATE KEY UPDATE position = VALUES(position)', [product.product_id, product.position, siteId]);
                    }
                    for (const variant of variantsWithPosition) {
                        await conn.query('UPDATE historicals SET position = ? WHERE product_id = ? AND variant_id = ? AND date_created = CURDATE() and site_id = ?', [variant.position + 1, variant.product_id, variant.variant_id, siteId])
                    }

                })();
            }
            resolve('true');
        });


    })

}
