var webpage = require('webpage');
var fs = require('fs');


module.exports = function (data, done, worker) {

    const params = data;
    const page = webpage.create();

    const writeData = function (data) {

    }
    page.open(params.url, function (status) {

        const content = page.content;
        const path = 'data/quantity/' + params.hostname + params.productId + '.csv';

        if (fs.exists(path)) {


            fs.remove(path);
        }

        fs.touch(path);
        fs.write(path, content, 'a');
        done(null);


    });


}
;
