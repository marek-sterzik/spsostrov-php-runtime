import $ from "jquery"

function composeTransform(t1, t2)
{
    var t = "translate("+ t1.x +" "+ t1.y +")";
    if (t2 !== '' && t2 !== null && t2 !== undefined) {
        t += ' ' + t2;
    }
    return t;
}


function animateBirdHoop(val, max, bird)
{
    var max = -10;

    var current = 1 - ((50 - val)*(50 - val))/2500;
    
    var translate = {x: 0, y: current*max};

    bird.ptacekStin.attr("transform", composeTransform(translate, bird.ptacekStinTransformation));
    bird.ptacek.attr("transform", composeTransform(translate, bird.ptacekTransformation));
}

function setBeakState(angle, bird)
{
    var rot1 = "rotate("+(-angle)+" "+bird.zobacekStred.x+" "+bird.zobacekStred.y+")";
    var rot2 = "rotate("+angle+" "+bird.zobacekStred.x+" "+bird.zobacekStred.y+")";
    var rot1Stin = "rotate("+(-angle)+" "+bird.zobacekStinStred.x+" "+bird.zobacekStinStred.y+")";
    var rot2Stin = "rotate("+angle+" "+bird.zobacekStinStred.x+" "+bird.zobacekStinStred.y+")";
    
    bird.zobacek1.attr("transform", rot1);
    bird.zobacek2.attr("transform", rot2);
    bird.zobacek1Stin.attr("transform", rot1Stin);
    bird.zobacek2Stin.attr("transform", rot2Stin);
}

function setBlinkState(s, bird)
{
    var vx = -.5;
    var vy = -1;
    var t = {x: vx*s, y: vy*s};
    bird.vicko.attr("transform", composeTransform(t, bird.vickoTransformation));
}



function sumFreqs(time, tweetFreqs)
{
    var sums = {
        "min": 0,
        "max": 0,
        "sum": 0
    }
    for (var i = 0; i < tweetFreqs.length; i++) {
        var freq = tweetFreqs[i];
        sums.max += freq.amplitude;
        sums.min -= freq.amplitude;
        sums.sum += freq.amplitude * Math.sin(2*Math.PI*(time - freq.shift)/freq.period);
    }

    return sums;
}

function animateBirdTweetStep(val, tweetLength, tweetFreqs, beakOpenTime, bird)
{
    var maxAngle = 10;

    var scale;

    if (val < beakOpenTime) {
        val = 0;
        scale = val/beakOpenTime;
    } else if (val < beakOpenTime + tweetLength) {
        val = val - beakOpenTime;
        scale = 1;
    } else {
        scale = Math.max(0, 1 - (val - (beakOpenTime + tweetLength))/beakOpenTime)
        val = beakOpenTime + tweetLength;
    }

    var sums = sumFreqs(val, tweetFreqs);

    var angle = scale*maxAngle*(sums.sum - sums.min)/(sums.max - sums.min);

    setBeakState(angle, bird);
}

function animateBirdStep(val, hoopLength, pauseLength, bird)
{
    var max = -2;
    
    var cycleLength = hoopLength + pauseLength;
    
    while (val > cycleLength) {
        val -= cycleLength;
    }

    if (val <= hoopLength) {
        val = (val/hoopLength)*100;
    } else {
        val = 0;
    }

    animateBirdHoop(val, max, bird);
}

var carouselPausedLevel = 0;

function animateBird(hoopLength, pauseLength, hoops, bird)
{
    if (bird.tweeting || bird.hooping) {
        return;
    }

    bird.hooping = true;

    var duration = hoopLength * hoops + pauseLength * (hoops - 1);
    $({time: 0}).animate({time: duration}, {
        step: function(val){
            animateBirdStep(val, hoopLength, pauseLength, bird)
        },
        duration: duration,
        easing: "linear",
        done: function() {
            bird.hooping = false;
            tweetBird(bird);
        }
    });
}

var numberOfTweets = 0;

function tweetBird(bird)
{
    var tweetLength = 2500;
    var beakOpenTime = 50;
    var tweetFreqs = [
        {"period": 500, "amplitude": 2, "shift": 0},
        {"period": 110, "amplitude": 1, "shift": 0}
    ];

    if (bird.tweeting) {
        return;
    }

    bird.tweeting = true;

    var duration = 2*beakOpenTime + tweetLength;
    
    $({time: 0}).animate({time: duration}, {
        step: function(val){
            animateBirdTweetStep(val, tweetLength, tweetFreqs, beakOpenTime, bird);
        },
        duration: duration,
        easing: "linear",
        done: function() {
            bird.tweeting = false;
            numberOfTweets++;
            if (numberOfTweets == 3) {
                $('#prepinac').animate({"opacity": 1});
            }
        }
    });
    $('#sound')[0].play();

}

function parseStartPoint(path)
{
    var d = path.attr('d').split(/\s/);
    if (d.length < 2 || d[0] != 'm') {
        return null;
    }
    
    var p = d[1].split(/,/);

    if (p.length != 2) {
        return null;
    }

    var pt = {
        "x": parseFloat(p[0]),
        "y": parseFloat(p[1])
    }

    if (isNaN(pt.x) || isNaN(pt.y)) {
        return null;
    }

    return pt;
}

function getBird(svgDoc)
{
    var items = {
        'ptacek': 'ptacek',
        'ptacek-stin': 'ptacekStin',
        'zobacek1': 'zobacek1',
        'zobacek1-stin': 'zobacek1Stin',
        'zobacek2': 'zobacek2',
        'zobacek2-stin': 'zobacek2Stin',
        'vicko': 'vicko'
    };
    var bird = {};
    for (var i in items) {
        bird[items[i]] = $(svgDoc).find('#'+i);
    }

    bird.zobacekStred = parseStartPoint(bird.zobacek2);
    bird.zobacekStinStred = parseStartPoint(bird.zobacek2Stin);
    bird.ptacekTransformation = bird.ptacek.attr('transform');
    bird.ptacekStinTransformation = bird.ptacekStin.attr('transform');
    bird.vickoTransformation = bird.vicko.attr('transform');
    bird.tweeting = false;
    bird.hooping = false;
    return bird;
}

function startBirdAction(bird)
{
    //tweetBird(bird);
    animateBird(200, 200, 3, bird);
}

function birdBlinkEye(bird)
{
    var duration = 300;

    $({time: 0}).animate({time: duration}, {
        step: function(val){
            var s = 4 * val * (val - duration) / (duration * duration);
            setBlinkState(s, bird);
        },
        duration: duration,
        easing: "linear",
    });
}

function enableBird(element)
{
    element.each(function(){
        var svgDoc = this.getSVGDocument();
        var bird = getBird(svgDoc);
        bird.ptacek.css("cursor", "pointer");
        bird.ptacek.on("click", function() {
            startBirdAction(bird);
        });
        setInterval(function() {
            birdBlinkEye(bird);
        }, 5000);
    });
}

$(window).on("load", function() {
    enableBird($('object.ptacek'));
    $('#prepinac').css("opacity", 0).css("visibility", "visible");
    $('#prepinac-zpet').css("opacity", 0).css("visibility", "visible");
    $('#prepinac a').bind("click", function(ev) {
        $(".sloupek-kontejner").show();
        $(".ptacek-kontejner").hide();
        setTimeout(function(){
            $("video.sloupek")[0].play();
            setTimeout(function(){
                $('#prepinac-zpet').animate({"opacity": 1});
            }, 5000);
        }, 5000);
        ev.preventDefault();
        return false;
    });
    $('#prepinac-zpet a').bind("click", function(ev) {
        $(".sloupek-kontejner").hide();
        $(".ptacek-kontejner").show();
        var video = $('video.sloupek')[0];
        video.pause();
        video.currentTime = 0;
        ev.preventDefault();
        return false;
    });
    $('video.sloupek').bind("click", function() {
        $(this)[0].play();
    });
});
