var ssaCompatibleBrowser=function(){var e="function"==typeof Object.__defineSetter__,t=!!Array.prototype.find,n=!!document.createElementNS&&!!document.createElementNS("http://www.w3.org/2000/svg","svg").createSVGRect,r="transition"in document.documentElement.style||"WebkitTransition"in document.documentElement.style;return e&&t&&function(){try{return!0}catch(e){return!1}}()&&n&&r&&function(){var e="flex",t="-webkit-"+e,n=document.createElement("b");try{return n.style.display=t,n.style.display=e,!(n.style.display!==e&&n.style.display!==t)}catch(e){return!1}}()&&function(){try{return!!new Intl.NumberFormat("en-US",{style:"currency",currency:"USD"}).format(123.456)}catch(e){return!1}}()};if(!ssaCompatibleBrowser()){var adminApp=document.getElementById("ssa-admin-app");adminApp&&adminApp.parentNode.removeChild(adminApp);var bookingApp=document.getElementById("ssa-booking-app");bookingApp&&bookingApp.parentNode.removeChild(bookingApp);var message=document.getElementById("ssa-unsupported");throw message.style.display="block",new Error("Your browser is not compatible")}
