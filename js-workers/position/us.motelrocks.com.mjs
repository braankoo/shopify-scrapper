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


export default function (csv) {
    return new Promise(async function (resolve, reject) {
        fs.readFile(csv, 'utf8', async function (err, data) {

            let products = Array.from(new Set(data.match(/[0-9]{0,}/g)));
            products.filter(function (product) {
                return Number.isInteger(parseInt(product));
            })
            for (let i = 0; i < products.length; i++) {
                await conn.query('UPDATE products SET position = ? WHERE product_id = ?', [i + 1, products[i]]);
                await conn.query('INSERT INTO product_position (product_id,position,date_created) VALUES (?,?, CURDATE()) ON DUPLICATE KEY UPDATE position = VALUES(position)', [products[i], i + 1]);
            }
            resolve('true');
        });


    });

}
