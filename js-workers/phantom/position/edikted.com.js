var webpage = require('webpage');
var fs = require('fs');


module.exports = function (data, done, worker) {

    var params = data;

    var page = webpage.create();

    var fail = 0;

    const writeData = function (data) {
        fs.write(params.filePath, data, 'a');
    }

    const loadPage = function (url, pageId) {

        page.open(url + '?page=' + pageId, function (status) {

            if (fail === 10) {

                done(null);
                return;
            }
            if (pageId === 7) {
                done(null);
            }


            if (status !== 'success') {
                fail++;

                setTimeout(function () {
                    loadPage(url, pageId);
                }, 10000);
                return;

            }

            const productsHtml = page.evaluate(function () {
                return document.querySelector('.ProductList').children.length > 0;
            });

            console.log('****');
            console.log(productsHtml);
            console.log('****');

            if (!productsHtml) {
                fail++;
                loadPage(url, pageId);


            } else {
                const content = page.evaluate(function () {
                    return document.querySelector('.ProductList').outerHTML;
                });


                const toWrite = content.match(/data-id=".\d*/g);
                writeData(toWrite.toString());
                loadPage(url, ++pageId);


            }
        });

    }


    loadPage(data.url, 1);
};
