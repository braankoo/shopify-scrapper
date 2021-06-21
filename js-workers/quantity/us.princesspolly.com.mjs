import fs from 'fs';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import _ from 'lodash';

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

export default function (productId, csv) {
    return new Promise(function (resolve, reject) {
        fs.readFile(csv, 'utf8', async function (err, data) {
            if (typeof data !== "undefined") {
                let variantsQuantity = data.match(/_BISConfig.product.variants\[\d]\['inventory_quantity'] = \d.*;/g);

                if (Array.isArray(variantsQuantity)) {
                    variantsQuantity = _.uniq(variantsQuantity);
                    for (const row of variantsQuantity) {

                        await (async () => {
                            let variantQuantity = row.match(/[0-9]{1,10}/g);
                            const variant = variantQuantity[0];
                            const quantity = variantQuantity[1];
                            const [rows, fields] = await conn.query('SELECT variant_id FROM variants where product_id = ?', [productId]);
                            const variantRawObj = rows[variant];
                            await conn.query('UPDATE historicals SET inventory_quantity = ? WHERE variant_id = ? and date_created = CURDATE()', [quantity, variantRawObj.variant_id]);
                            try {
                                const [r, f] = await conn.query('SELECT inventory_quantity from historicals WHERE date_created = SUBDATE(CURDATE(),1) and variant_id = ?', [variantRawObj.variant_id]);
                                if (r.length > 0) {
                                    await conn.query('UPDATE historicals set sales = ?  WHERE date_created = SUBDATE(CURDATE(),1) and variant_id = ?', [r[0].inventory_quantity - quantity, variantRawObj.variant_id]);
                                }
                            } catch (err) {
                                throw err;
                            }
                        })();

                    }
                }
            }
            resolve('true');
        });


    });
}
