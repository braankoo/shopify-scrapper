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
                    const [rows, fields] = await conn.query('SELECT product_id,id FROM variants WHERE id IN (?)', [chunk]);

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

                        console.log('*****');
                        console.log(product.position, product.product_id, siteId);
                        console.log('*****');
                        await conn.query('UPDATE products SET position = ? WHERE id = ? and site_id = ?', [product.position, product.product_id, siteId]);
                        await conn.query('INSERT INTO product_position (product_id,date_created,site_id,position) VALUES (?,CURDATE(),?,?) ON DUPLICATE KEY UPDATE position = VALUES(position)', [product.product_id, siteId, product.position]);
                    }

                })();
            }
            resolve('true');
        });


    })

}
