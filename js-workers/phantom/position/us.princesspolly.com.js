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

            if (fail === 10) {
                fail = 0;
                loadPage(url, ++pageId);
                return;
            }
            if (pageId === 50) {
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
                return document.getElementById('bc-sf-filter-products').children.length;
            });

            if (productsHtml === 0) {
                loadPage(url, pageId);


            } else {
                const content = page.evaluate(function () {
                    return document.getElementById('bc-sf-filter-products').outerHTML;
                });


                const isLastPage = page.evaluate(function () {
                    return document.getElementsByClassName('paginate__link--next')[0].className.includes('--disabled');
                });


                if (isLastPage) {
                    done(null);

                } else {
                    const toWrite = content.match(/data-product-selected-variant=".\d*/g);
                    writeData(toWrite.toString());
                    loadPage(url, ++pageId);

                }
            }
        });

    }


    loadPage(data.url, pageId);
};
