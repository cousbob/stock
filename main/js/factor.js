/*http://openhome.cc/Gossip/JavaScript/Closure.html*/

function prepareFactor(max) {
     var prime = new Array(max + 1);
     for(var i = 2; i * i <= max; i++) {
         if(prime[i] == undefined) {
             for(var j = 2 * i; j <= max; j++) {
                 if(j % i == 0) {
                     prime[j] = 1;
                 }
             }
         }
     }
     var primes = [];
     for(var i = 2; i <= max; i++) {
         if(prime[i] == undefined) {
             primes.push(i);
         }
     }
     // factor 會綁定 primes
     function factor(num) {
         var list = [];
         for(var i = 0; primes[i] * primes[i] <= num;) {
             if(num % primes[i] == 0) {
                 list.push(primes[i]);
                 num /= primes[i];
             }
             else {
                 i++
             }
         }
         list.push(num);
         return list;
     }
     return factor;
}