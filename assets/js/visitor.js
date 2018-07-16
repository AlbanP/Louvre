$(document).ready(function() {
    
    var local = $('#local').data('local');
    var path = $('.visitor-select').data('path');
    var $container = $('div#app_ticket_visitors');
    var index = $container.find(':input').length;
    var deleteBtn = $('.visitor-select').data('delete');
    var priceBtn = $('.visitor-select').data('price');

    $('.birthday').each(function(){
        calculPrice($(this).attr('id'));
    });

    $('#app_ticket_visitors').on('change', 'input[type="checkbox"]', function() {
        calculPrice($(this).attr('id'));  
    });   
    
    $('#app_ticket_visitors').on('focusin', 'input[type="date"]', function() {
        $('#app_ticket_pay').css('display', 'none');
        $('#btn-calcul').css('display', 'inline');
    });

    $('#app_ticket_visitors').on('focusout', 'input[type="date"]', function() {
        calculPrice($(this).attr('id'));
        $('#btn-calcul').css('display', 'none');
        $('#app_ticket_pay').css('display', 'inline');
    });

    $('#add_visitor').on('click', function(e) {
        addVisitor($container);
        e.preventDefault();
        return false;
    });

    if (index == 0) {
        addVisitor($container);
    } else {
        $container.children('div').each(function() {
            addWidget($(this));
        });
    }

    function addVisitor($container) {
        var template = $container.attr('data-prototype').replace(/__name__/g, index);
        var $prototype = $(template);

        addWidget($prototype);
        $container.append($prototype);
        //
        var prototypeId = $prototype.children(":first").attr('id');
        var visitorPrev = $('#' + prototypeId).parent().prev();
        var namePrev = visitorPrev.find('.name').val();
        var countryPrev = visitorPrev.find('.country option:selected').val();
        $('#' + prototypeId).find('.name').val(namePrev);
        $('#' + prototypeId).find('.country').val(countryPrev);

        index++;
    }

    function addWidget($prototype) {
        var $deleteLink = $("<div class='visitor-delete'><a href='#' class='btn btn-delete'>" + deleteBtn + "</a></div>");
        var $priceIndicator = $("<div class='visitor-price'>" + priceBtn + "<span class='price'></span> €</div>");
        $prototype.children().append($priceIndicator);
        $prototype.children().append($deleteLink);

        $deleteLink.click(function(e) {
            $prototype.remove();
            e.preventDefault();
            priceTotal();

            return false;
        });
    }

    function calculPrice(id) {
        var ticket = id.split('_');
        var pathVisitor = '#app_ticket_visitors_' + ticket[3];
        var reduction = $(pathVisitor + '_reduction:checked').val();
        if (reduction == undefined) {reduction = 0;}
        var birthday = $(pathVisitor + '_birthday').val();
        birthday = birthday.replace( /\//g, '-');
        var dateVisit = $('.date-day').data('date-visit');

        $.ajax({
            url : path,
            method: "POST",
            data: {
            birthday: birthday,
            reduction: reduction,
            dateVisit: dateVisit
            }
        }).done(function(data){
            $(pathVisitor + '_birthday').parent().find('ul').remove();
            if ($(pathVisitor + '_birthday').val() != ''){
                var birthday = new Date(data.birthday);
                $(pathVisitor + '_birthday').val(birthday.getFullYear() + '-' + digit2(birthday.getMonth() +1 ) + '-' + digit2(birthday.getDate()));
            }
            var pathPrice = pathVisitor + ' .price';
            $(pathPrice).text(data.price/100);
            priceTotal();
        }).fail(function(){
            if (local == 'fr') {
                $(pathVisitor + '_birthday').before("<ul><li>Mauvaise date (ex: 14-07-1789)</li><ul>");
            } else {
                $(pathVisitor + '_birthday').before("<ul><li>Bad date (ex: 1789-07-14)</li><ul>");
            }
            $(pathVisitor + ' .price').text('');
        })
    }

    function priceTotal() {
        var totalPrice = 0;
        $('.price').each(function() {
            if($(this).text()) {
                totalPrice += parseInt($(this).text());
            }
        });
        $('#total_price').text(totalPrice);
    }

    function digit2(number) {
        return (number < 10 ? '0' : '') + number
    }
  });