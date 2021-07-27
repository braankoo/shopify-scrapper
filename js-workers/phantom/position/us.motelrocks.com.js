var webpage = require('webpage');
var fs = require('fs');


module.exports = function (data, done, worker) {
    var params = data;

    var page = webpage.create();
    var pageId = 1;
    var fail = 0;

    const writeData = function (data) {
        fs.write(params.filePath, data, 'a');
    }
    const loadPage = function (url, pageId) {

        page.open(url + '&page=' + pageId, function (status) {

            console.log('*****');

            console.log(fail);
            console.log('*****');
            console.log('*****');
            console.log(status);
            if (fail === 500) {
                done(null);
            }

            if (status !== 'success') {
                fail++;

                setTimeout(function () {
                    loadPage(url, pageId);
                }, 30000);
                return;

            }


            const productsHtml = page.evaluate(function () {
                return document.getElementById('bc-sf-filter-load-more').getAttribute("style");
            });
            if (productsHtml === 'display: none;') {
                done(null);
            }

            const loadedProp = page.evaluate(function () {
                return document.getElementById('bc-sf-filter-products').children.length;
            });

            conosle.log('***');
            console.log(loadedProp);
            conosle.log('***');

            if (loadedProp > 0) {

                const content = page.evaluate(function () {
                    return document.getElementById('bc-sf-filter-products').outerHTML;
                });

                const toWrite = content.match(/bc-product-json-.\d*/g);
                writeData(toWrite.toString());
                loadPage(url, ++pageId);


            } else {
                fail++;
                setTimeout(function () {
                    loadPage(url, pageId);
                }, 2000);
            }
        });

    }
    loadPage(data.url, 1);
}
