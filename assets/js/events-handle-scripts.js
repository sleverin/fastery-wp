(function ($, undefined) {
    $(document).ready(function () {

        var map,
            clusterer,
            myObjectManager,
            balloonStyle;

        var cityChange = true;
        var objects = [];
        var costs = [];

        if (0 != $('#ignet-yandex-map').length) {
            ymaps.ready(initMap);
        }

        /**
         * Установить значение
         */
        $('#billing_city').live('change', function (event) {
            $('#calc_shipping_city').val($('#billing_city').val());
        });

        /**
         * Работа с картой
         */
        $('#ignet-open-map').live('click', function (event) {
            city = $('#calc_shipping_city').val();
            cost = $('#ignet_cart_cost').val();
            weight = $('#ignet_cart_weight').val();
            if (!city || '' == city) {
                return;
            }

            $('.ignet-modal').css({'display': 'block'});

            if (!cityChange) {
                return;
            }
            cityChange = false;
            getPoints(city, cost, weight);
        });

        /**
         * Отправка запроса
         */
        function getPoints(city, cost, weight) {

            $.ajax({
                url: eventsHandle.url,
                type: 'POST',
                data: {
                    'action': eventsHandle.action,
                    'city': city,
                    'cost': cost,
                    'weight': weight,
                },
                success: function (respond, textStatus, jqXHR) {

                    var myObjects = [];
                    var currentId = 0;
                    var points = respond['points'];
                    costs = [];

                    $.each(points, function (index, item) {

                        caption = item['carrier_key'].toUpperCase() + ': ' + item['point_address'];

                        myObjects.push({
                            type: 'Feature',
                            id: currentId++,
                            geometry: {
                                type: 'Point',
                                coordinates: [item['lat'], item['lng']]
                            },
                            properties: {
                                balloonContent: generateBallonContent(item),
                                clusterCaption: '<strong>' + caption + '</strong>',
                                cost: item['cost']
                            }
                        });

                        costs.push(parseInt(item['cost']));
                    });

                    if (0 == costs.length) {

                        cost_min = 0;
                        cost_max = 0;

                        initSlider(cost_min, cost_max);
                        $('.ignet-range-block .range-input').slider('option', 'values', [cost_min, cost_max]);
                        $('.ignet-range-block .range-input').slider('option', 'min', cost_min);
                        $('.ignet-range-block .range-input').slider('option', 'max', cost_max);

                    } else {

                        costs.sort(function (a, b) {
                            return a - b;
                        });

                        cost_min = costs[0];
                        cost_max = costs[costs.length - 1];

                        initSlider(cost_min, cost_max);

                        $('.ignet-range-block .range-input').slider('option', 'values', [cost_min, cost_max]);
                        $('.ignet-range-block .range-input').slider('option', 'min', cost_min);
                        $('.ignet-range-block .range-input').slider('option', 'max', cost_max);

                        // Добавление коллекции объектов.
                        currentId = 0;
                        var collection = {
                            type: 'FeatureCollection',
                            features: myObjects
                        }
                        myObjectManager.add(collection);
                        map.geoObjects.add(myObjectManager);
                        map.setBounds(myObjectManager.getBounds(), {
                            checkZoomRange: true
                        });
                    }
                }
            });
        }

        /**
         * Инициализация карты
         */
        function initMap() {
            map = new ymaps.Map("ignet-yandex-map", {
                center: [55.76, 37.64],
                zoom: 9,
                controls: []
            });
            map.controls.add('zoomControl', {
                float: 'left',
            });
            initObjectManager();
        }

        /**
         * Инициализация кластера
         */
        function initObjectManager() {
            myObjectManager = new ymaps.ObjectManager({
                clusterize: true,
                clusterHideIconOnBalloonOpen: false,
                geoObjectHideIconOnBalloonOpen: false
            });
        }

        /**
         * Генерация контента для балуна
         *
         * @param item
         * @returns {string|*}
         */
        function generateBallonContent(item)
        {
            balloonContent = '<div class="fastery-balloon-block">';
            balloonContent = balloonContent +
                '<p class="map-header">' + item['point_address'] + '</p>';

            if (null != item['phone']) {
                balloonContent = balloonContent +
                    '<p class="point-phone"><b>Телефон: </b>' + item['phone'] + '</p>';
            }

            balloonContent = balloonContent +
                '<p class="point-cost"><b>Цена: </b>' + item['cost'] + '</p>' +
                '<p class="point-select">' +
                '<input class="fastery-btn" type="button" value="Выбрать" ' +
                'data-uid="' + item['uid'] + '"' +
                'data-address="' + item['point_address'] + '"' +
                'data-cost="' + item['cost'] + '"' +
                'data-min_term="' + item['min_term'] + '"' +
                '></p>' +
                '</div>';
            return balloonContent;
        }

        /**
         * Выбор ПВЗ
         */
        $('.point-select input').live('click', function () {
            uid = $(this).data('uid');
            address = $(this).data('address');
            cost = $(this).data('cost');
            min_term = $(this).data('min_term');
            if (min_term == 0) {
                min_term = 1;
            }

            address = address.replace($('#billing_city').val() + ',', '');
            address = address.trim();

            $('#ignet_fastery_pvz_field').val(address);
            $('#billing_address_1').val(address);
            $('#billing_address_2').val('');
            $('#ignet_fastery_uid').val(uid);
            $('#ignet_fastery_cost').val(cost);
            $('#ignet_fastery_delivery_term').val(min_term);

            Cookies.set('fastery-uid', uid, {path : '/', expires: 7});
            Cookies.set('fastery-cost', cost, {path : '/', expires: 7});
            Cookies.set('fastery-min-term', min_term, {path : '/', expires: 7});
            Cookies.set('fastery-address', address, {path : '/', expires: 7});

            $('#billing_address_1').change();
            $.ajax({
                url: eventsHandle.url,
                type: 'POST',
                data: {
                    'action': 'save_cost',
                    'cost': cost,
                },
                success: function (respond, textStatus, jqXHR) {

                }
            });
            $('#calc_fastery_pvz_field').html('<b>Адрес пункта выдачи: <br></b>' + address);
            $('#calc_fastery_cost').html('<b>Стоимость: </b>' + cost + ' руб.');
            $('#calc_fastery_delivery_term').html('<b>Срок доставки: </b> от ' + min_term + ' д.');
            $('.ignet-modal').css({'display': 'none'});
            $('input[type="radio"][value="fastery"]').click().trigger('change').change();
        });

        /**
         * Удаляем метки на карте при изменении города
         */
        $('#calc_shipping_city').live('change', function () {
            cityChange = true;
            map.geoObjects.removeAll;
            initObjectManager();
        });

        $('#billing_city').live('change', function () {
            cityChange = true;
            map.geoObjects.removeAll;
            initObjectManager();
        });

        /**
         * Закрыть модальное окно
         */
        $('.ignet-close-modal').on('click', function () {
            $('.ignet-modal').css({'display': 'none'});
        });

        /**
         * Инициализация слайдера
         */
        function initSlider(cost_min, cost_max) {
            $('.ignet-range-block .range-min').html(cost_min + ' р.');
            $('.ignet-range-block .range-max').html(cost_max + ' р.');
            $('.ignet-range-block .range-input').slider({
                min: 0,
                max: 10,
                values: [0, 10],
                range: true,
                stop: function (event, ui) {
                    min = $('#ignet-map-slider').slider('values', 0);
                    max = $('#ignet-map-slider').slider('values', 1);

                    $('.ignet-range-block .range-min').html(min + ' р.');
                    $('.ignet-range-block .range-max').html(max + ' р.');

                    if ('undefined' !== ymaps) {

                        myObjectManager.setFilter('properties.cost >= ' + min + ' && properties.cost <= ' + max);
                    }
                },
                slide: function (event, ui) {
                    min = $('#ignet-map-slider').slider('values', 0);
                    max = $('#ignet-map-slider').slider('values', 1);
                    $('.ignet-range-block .range-min').html(min + ' р.');
                    $('.ignet-range-block .range-max').html(max + ' р.');
                    if ('undefined' !== ymaps) {
                        myObjectManager.setFilter('properties.cost >= ' + min + ' && properties.cost <= ' + max);
                    }
                }
            });
        }
    });
})(jQuery);