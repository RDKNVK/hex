"use strict";

var SVGDoc, svginside;
var mouseOut = function () {};
var mouseOver = function () {};

var game_state = {
    A : [],
    B : [],
    winning : []
};

var PLAYER = {
    P1_color : '#6666FF',
    P2_color : '#FF6666',
    P1_hover : 'blue',
    P2_hover : 'red',
    set : false,

    P1: false,
    onturn: false,
    colour: this.P2_color,
    hover_color: this.P2_hover,
    opponent: this.P1_color,

    setColors: function () {
        if (!this.set) {
            if (this.P1) {
                this.colour = this.P1_color;
                this.hover_color = this.P1_hover;
                this.opponent = this.P2_color;
            } else {
                this.colour = this.P2_color;
                this.hover_color = this.P2_hover;
                this.opponent = this.P1_color;

            }
            mouseOver = mouse(PLAYER.hover_color);
            mouseOut = mouse('white');
            this.set = true;
        }
    }
};

var timer = {
    interval : null,
    time : 1000,
    running : false,

    start: function () {
        if (!this.running) {
            this.interval = window.setInterval(this.fire, this.time);
            this.running = true;
        }
    },

    stop: function () {
        if (this.interval) {
            window.clearInterval(this.interval);
        }
        this.running = false;
    },

    fire : function () {
        //refresh();
        server.receive();
        if (PLAYER.onturn) {
            this.stop();
        }
    }
};

var server = {
    game : '',
    DOMAIN : '',

    create: function (sides) {

        var that = this;
        sides = sides || 13;

        $.ajax({
            url: this.DOMAIN + 'create_game.php?sides=' + sides,
            success: function(data) {
                that.game = data;
            },
            async: false
        });

        var u = window.location.href + '?g=' + this.game;
        console.log(u);
        PLAYER.P1 = true;

        this.receive();
        $(location).attr('href', u);
    },

    send: function (move) {
        if (!PLAYER.onturn) {
            return;
        }

        var url = '';
        url = this.DOMAIN + 'game.php?g=' + this.game;

        if (move) {
            url += '&m=' + move;
        }

        $.ajax({
            url: url,
            success: function(data) {
                server.process_data(data);
            },
            async: false
        });
       
    },

    receive: function () {
        var url = this.DOMAIN + 'game.php?g=' + this.game;

        $.get(url, function(data) {
            server.process_data(data);
        });

        //PLAYER.setColors();
    },

    process_data: function (input) {
        var resp;
        try {
            resp = JSON.parse(input);
        } catch (e) {
            timer.stop();
            return;
        }
        
        function message(mes) {
            $('#message').html(mes);
        }

        PLAYER.onturn = resp.your_turn;
        
        if (resp.game_started) {
            if (!PLAYER.set) {
                PLAYER.P1 = resp.P1;
                PLAYER.setColors();
            }

            if (PLAYER.onturn) {
                message('Your turn');
                timer.stop();
            } else {
                timer.start();
                message('Opponent\'s turn');
            }

            if (resp.winner) {
                timer.stop();
                game_state.winning = resp.winning_moves;

                if ((resp.winner === 'P1' && PLAYER.P1) || (resp.winner === 'P2' && !PLAYER.P1)) {
                    message('You won!');
                } else {
                    message('You lost.');
                }
                /*
                var win_color = (resp.winner === 'P1') ? '#0000FF' : '#FF0000';

                for (var i = 0, ii = game_state.winning.length; i < ii; i++){
                    var id = game_state.winning[i];
                    setFieldColor(id, win_color);
                }
                */
            }
            game_state.A = resp.P1_moves;
            game_state.B = resp.P2_moves;
            refresh();
        } else {
            var u = window.location.href;
            message('Send this link: <a id="gamelink" href=\"' + u + '\">' + u + '</a>');
        }
    }
};

function refresh() {
    var id, c, i, ii;
    for (i = 0, ii = game_state.A.length; i < ii; i++) {
        id = game_state.A[i];
        c = PLAYER.P1 ? PLAYER.colour : PLAYER.opponent;
        setFieldColor(id, c);
    }

    for (i = 0, ii = game_state.B.length; i < ii; i++) {
        id = game_state.B[i];
        c = PLAYER.P1 ? PLAYER.opponent : PLAYER.colour;
        setFieldColor(id, c);
    }
}

function setFieldColor(fieldId, color) {

    function attrReplace(oldstring, attr, new_val) {
        var re, toReplace, ret;
        re = new RegExp('(' + attr + ':.*?);');
        toReplace = re.exec(oldstring)[1];
        ret = oldstring.replace(toReplace, attr + ':' + new_val);
        return ret;
    }

    var el = SVGDoc.getElementById(fieldId);
    var cur_style = el.getAttribute('style');
    
    el.setAttribute('style', attrReplace(cur_style, 'fill', color));
}

function init() {
    SVGDoc = document.getElementById("E").getSVGDocument();
    svginside = SVGDoc.getElementById('svg6176');
    //svginside.setAttribute('viewBox', '0 0 985 595');
    var addr = $(location).attr('href');

    server.DOMAIN = /(.*\/).*?\..*?/.exec(addr)[1];
    timer.start();

    
    $(window).resize(function  () {
        svginside.setAttribute('width', $(window).width());
    });

    // old doc.ready
    
    var reParam = /\?(g)=([A-Za-z0-9]*)/;
    var game = reParam.exec(addr);

    if (game){
        $('#btn-create').hide(0);
        $('#intro').hide(0);
        server.game = game[2];
    }
    else {
        $('#svg-container').hide(0);
    }
}

function click(evt) {
    var coord = evt.target.getAttribute('id');
    if (!(game_state.A.indexOf(coord) > -1)
            && !(game_state.B.indexOf(coord) > -1)
            && game_state.winning.length === 0) {
        server.send(coord);
    }
}

function mouse(color) {
    return function (evt) {
        var id = evt.target.getAttribute('id');

        if (!(game_state.A.indexOf(id) > -1)
                && !(game_state.B.indexOf(id) > -1)
                && game_state.winning.length === 0) {

            setFieldColor(id, color);
        }
    };
}
