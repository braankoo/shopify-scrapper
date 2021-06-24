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
        url = url.replace(/i=[0-9]{1,2}/g, 'i=' + pageId);

        if (pageId === 1) {
            done(null);
            return;
        }
        console.log(pageId);

        page.open(url, function (status) {
            console.log(status);
            if (fail === 10) {
                fail = 0;
                loadPage(url, ++pageId);
                return;
            }


            if (status !== 'success') {
                fail++;

                setTimeout(function () {
                    loadPage(url, pageId);
                }, 10000);
                return;

            }

            const productsHtml = page.evaluate(function () {
                return document.getElementById('product-grid').children.length > 0;
            });

            if (productsHtml === 0) {
                loadPage(url, pageId);

            } else {

                const content = page.evaluate(function () {
                    return document.getElementById('product-grid');
                });
                console.log(content);


                writeData(content.outerHTML);

                loadPage(url, ++pageId);


            }
        });

    }


    loadPage(data.url, 0);
};
