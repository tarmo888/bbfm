// blue
var lineCol = '#e8e8e8';
// var fillCol = '#366ba0';

// var lineCol = '#eb5633';
// var fillCol = '#192735';
//red
// var lineCol = '#9a3219';
// green
// var lineCol = '#4cae4c';
// var fillCol = '#2c3e50';
var fillCol = '#2c3e50';

particlesJS("particles-js", {
  "particles": {
    "number": {
      "value": 9,
      "density": {
        "enable": true,
        "value_area": 800
      }
    },
    "color": {
      "value": fillCol
    },
    "shape": {
      "type": "circle",
      "stroke": {
        "width": 2,
        "color": lineCol
      },
      "polygon": {
        "nb_sides": 0
      },
      "image": {
        "src": "img/github.svg",
        "width": 100,
        "height": 100
      }
    },
    "opacity": {
      "value": 1,
      "random": false,
      "anim": {
        "enable": false,
        "speed": 0.1,
        "opacity_min": 0.1,
        "sync": false
      }
    },
    "size": {
      "value": 24.05118091298284,
      "random": true,
      "anim": {
        "enable": false,
        "speed": 9.234779642848423,
        "size_min": 0.1,
        "sync": false
      }
    },
    "line_linked": {
      "enable": true,
      "distance": 448.9553770423464,
      "color": lineCol,
      "opacity": 1,
      "width": 1.763753266952075
    },
    "move": {
      "enable": true,
      "speed": 1.5,
      "direction": "top-right",
      "random": true,
      "straight": false,
      "out_mode": "out",
      "bounce": false,
      "attract": {
        "enable": false,
        "rotateX": 7295.524876938129,
        "rotateY": 7055.0130678083
      }
    }
  },
  "interactivity": {
    "detect_on": "canvas",
    "events": {
      "onhover": {
        "enable": false,
        "mode": "bubble"
      },
      "onclick": {
        "enable": false,
        "mode": "repulse"
      },
      "resize": false
    },
    "modes": {
      "grab": {
        "distance": 400,
        "line_linked": {
          "opacity": 0.6745473540468723
        }
      },
      "bubble": {
        "distance": 400,
        "size": 4,
        "duration": 0.3,
        "opacity": 1,
        "speed": 3
      },
      "repulse": {
        "distance": 200,
        "duration": 0.4
      },
      "push": {
        "particles_nb": 4
      },
      "remove": {
        "particles_nb": 2
      }
    }
  },
  "retina_detect": true
});

