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

export default function (csv) {
    fs.readFile(csv, 'utf8', function (err, data) {

        let variants = Array.from(
            new Set(data.match(/data-product-selected-variant=".\d*/g)
                .map(function (variant) {

                    return variant.replace(/\D/g, '');
                })));

        const chunks = sliceIntoChunks(variants, 100);

        chunks.forEach(function (chunk) {

            conn.query('SELECT product_id,variant_id FROM variants WHERE variant_id IN (?)', [chunk], function (err, results) {
                if (err) throw err;

                const variantsWithPosition = results.map(function (result) {
                    result.position = variants.findIndex(function (variant) {
                        return variant == result.variant_id;
                    });

                    return result;
                });

                const productsWithPositions = variantsWithPosition.map(function (variant) {
                    const obj = {};
                    obj.product_id = variant.product_id;
                    obj.position = variant.position + 1;
                    return obj;
                });

                productsWithPositions.forEach(function (product) {
                    conn.query('UPDATE products SET position = ? WHERE product_id = ?', [product.position, product.product_id], function (err) {
                        if (err) throw err;
                    });
                });

                variantsWithPosition.forEach(function(variant){
                    conn.query('UPDATE historicals SET position = ? WHERE product_id = ? AND variant_id = ? AND date_created = CURDATE()' , [variant.position + 1, variant.product_id, variant.variant_id])
                })

            });
        })
    });
}
