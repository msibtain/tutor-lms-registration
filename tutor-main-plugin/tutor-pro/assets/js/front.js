(()=>{var t={34:function(){window.addEventListener("DOMContentLoaded",()=>{var n=document.getElementById("tutor-download-invoice");if(n){n.addEventListener("click",r=>{var o=n.dataset.orderId;n.classList.add("tutor-btn-loading");var a=document.getElementById("tutor-invoice-content");// Fix strikethrough elements before capture
var i=t(a);setTimeout(()=>{html2canvas(a,{scale:2,backgroundColor:"#ffffff",logging:false,windowWidth:a.scrollWidth,windowHeight:a.scrollHeight}).then(t=>{// Restore original strikethrough styles
e(i);var r=t.toDataURL("image/jpeg",1);var a=new jspdf.jsPDF({orientation:"p",unit:"mm",format:"a4"});var s=a.internal.pageSize.getWidth();var c=a.internal.pageSize.getHeight();var u=t.width;var d=t.height;var l=u/d;var f=10;var h=s-2*f;var m=c-2*f;var g=h;var w=g/l;if(w>m){w=m;g=w*l}a.addImage(r,"PNG",f,f,g,w);a.save("invoice-".concat(o,".pdf"));n.classList.remove("tutor-btn-loading")}).catch(t=>{e(i);n.classList.remove("tutor-btn-loading")})},0)})}});/**
 * Finds all elements with line-through and replaces the CSS decoration
 * with a visible absolutely-positioned <span> line that html2canvas can render.
 * Returns a list of elements to restore afterward.
 */function t(t){var e=[];t.querySelectorAll("*").forEach(t=>{var n=window.getComputedStyle(t);var r=n.textDecorationLine||n.textDecoration;if(r.includes("line-through")){// Wrap in a relative container if not already positioned
var o=window.getComputedStyle(t).position;if(o==="static"){t.style.position="relative";e.push({el:t,resetPosition:true})}else{e.push({el:t,resetPosition:false})}// Remove the CSS strikethrough
t.style.textDecoration="none";// Add a manual line overlay
var a=document.createElement("span");a.className="__strikethrough-overlay";a.style.cssText="\n                position: absolute;\n                left: 0;\n                right: 0;\n                top: 50%;\n                height: 1.5px;\n                background-color: ".concat(n.color,";\n                pointer-events: none;\n                transform: translateY(-50%);\n            ");t.appendChild(a)}});return e}/**
 * Restores original styles and removes injected overlay spans.
 */function e(t){t.forEach(t=>{var{el:e,resetPosition:n}=t;e.style.textDecoration="";if(n)e.style.position="";e.querySelectorAll(".__strikethrough-overlay").forEach(t=>t.remove())})}}};// The module cache
var e={};// The require function
function n(r){// Check if module is in cache
var o=e[r];if(o!==undefined){return o.exports}// Create a new module (and put it into the cache)
var a=e[r]={exports:{}};// Execute the module function
t[r](a,a.exports,n);// Return the exports of the module
return a.exports}// webpack/runtime/rspack_version
(()=>{n.rv=()=>"1.6.5"})();// webpack/runtime/rspack_unique_id
(()=>{n.ruid="bundler=rspack@1.6.5"})();// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(()=>{"use strict";// CONCATENATED MODULE: ./node_modules/.pnpm/@swc+helpers@0.5.17/node_modules/@swc/helpers/esm/_async_to_generator.js
function t(t,e,n,r,o,a,i){try{var s=t[a](i);var c=s.value}catch(t){n(t);return}if(s.done)e(c);else Promise.resolve(c).then(r,o)}function e(e){return function(){var n=this,r=arguments;return new Promise(function(o,a){var i=e.apply(n,r);function s(e){t(i,o,a,s,c,"next",e)}function c(e){t(i,o,a,s,c,"throw",e)}s(undefined)})}};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/constants.js
/**
 * @module constants
 * @summary Useful constants
 * @description
 * Collection of useful date constants.
 *
 * The constants could be imported from `date-fns/constants`:
 *
 * ```ts
 * import { maxTime, minTime } from "./constants/date-fns/constants";
 *
 * function isAllowedTime(time) {
 *   return time <= maxTime && time >= minTime;
 * }
 * ```
 *//**
 * @constant
 * @name daysInWeek
 * @summary Days in 1 week.
 */const r=7;/**
 * @constant
 * @name daysInYear
 * @summary Days in 1 year.
 *
 * @description
 * How many days in a year.
 *
 * One years equals 365.2425 days according to the formula:
 *
 * > Leap year occurs every 4 years, except for years that are divisible by 100 and not divisible by 400.
 * > 1 mean year = (365+1/4-1/100+1/400) days = 365.2425 days
 */const o=365.2425;/**
 * @constant
 * @name maxTime
 * @summary Maximum allowed time.
 *
 * @example
 * import { maxTime } from "./constants/date-fns/constants";
 *
 * const isValid = 8640000000000001 <= maxTime;
 * //=> false
 *
 * new Date(8640000000000001);
 * //=> Invalid Date
 */const a=Math.pow(10,8)*24*60*60*1e3;/**
 * @constant
 * @name minTime
 * @summary Minimum allowed time.
 *
 * @example
 * import { minTime } from "./constants/date-fns/constants";
 *
 * const isValid = -8640000000000001 >= minTime;
 * //=> false
 *
 * new Date(-8640000000000001)
 * //=> Invalid Date
 */const i=/* unused pure expression or super */null&&-a;/**
 * @constant
 * @name millisecondsInWeek
 * @summary Milliseconds in 1 week.
 */const s=6048e5;/**
 * @constant
 * @name millisecondsInDay
 * @summary Milliseconds in 1 day.
 */const c=864e5;/**
 * @constant
 * @name millisecondsInMinute
 * @summary Milliseconds in 1 minute
 */const u=6e4;/**
 * @constant
 * @name millisecondsInHour
 * @summary Milliseconds in 1 hour
 */const d=36e5;/**
 * @constant
 * @name millisecondsInSecond
 * @summary Milliseconds in 1 second
 */const l=1e3;/**
 * @constant
 * @name minutesInYear
 * @summary Minutes in 1 year.
 */const f=525600;/**
 * @constant
 * @name minutesInMonth
 * @summary Minutes in 1 month.
 */const h=43200;/**
 * @constant
 * @name minutesInDay
 * @summary Minutes in 1 day.
 */const m=1440;/**
 * @constant
 * @name minutesInHour
 * @summary Minutes in 1 hour.
 */const g=60;/**
 * @constant
 * @name monthsInQuarter
 * @summary Months in 1 quarter.
 */const w=3;/**
 * @constant
 * @name monthsInYear
 * @summary Months in 1 year.
 */const v=12;/**
 * @constant
 * @name quartersInYear
 * @summary Quarters in 1 year
 */const y=4;/**
 * @constant
 * @name secondsInHour
 * @summary Seconds in 1 hour.
 */const b=3600;/**
 * @constant
 * @name secondsInMinute
 * @summary Seconds in 1 minute.
 */const p=60;/**
 * @constant
 * @name secondsInDay
 * @summary Seconds in 1 day.
 */const M=/* unused pure expression or super */null&&b*24;/**
 * @constant
 * @name secondsInWeek
 * @summary Seconds in 1 week.
 */const _=/* unused pure expression or super */null&&M*7;/**
 * @constant
 * @name secondsInYear
 * @summary Seconds in 1 year.
 */const x=/* unused pure expression or super */null&&M*o;/**
 * @constant
 * @name secondsInMonth
 * @summary Seconds in 1 month
 */const k=/* unused pure expression or super */null&&x/12;/**
 * @constant
 * @name secondsInQuarter
 * @summary Seconds in 1 quarter.
 */const P=/* unused pure expression or super */null&&k*3;/**
 * @constant
 * @name constructFromSymbol
 * @summary Symbol enabling Date extensions to inherit properties from the reference date.
 *
 * The symbol is used to enable the `constructFrom` function to construct a date
 * using a reference date and a value. It allows to transfer extra properties
 * from the reference date to the new date. It's useful for extensions like
 * [`TZDate`](https://github.com/date-fns/tz) that accept a time zone as
 * a constructor argument.
 */const S=Symbol.for("constructDateFrom");// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/constructFrom.js
/**
 * @name constructFrom
 * @category Generic Helpers
 * @summary Constructs a date using the reference date and the value
 *
 * @description
 * The function constructs a new date using the constructor from the reference
 * date and the given value. It helps to build generic functions that accept
 * date extensions.
 *
 * It defaults to `Date` if the passed reference date is a number or a string.
 *
 * Starting from v3.7.0, it allows to construct a date using `[Symbol.for("constructDateFrom")]`
 * enabling to transfer extra properties from the reference date to the new date.
 * It's useful for extensions like [`TZDate`](https://github.com/date-fns/tz)
 * that accept a time zone as a constructor argument.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 *
 * @param date - The reference date to take constructor from
 * @param value - The value to create the date
 *
 * @returns Date initialized using the given date and value
 *
 * @example
 * import { constructFrom } from "./constructFrom/date-fns";
 *
 * // A function that clones a date preserving the original type
 * function cloneDate<DateType extends Date>(date: DateType): DateType {
 *   return constructFrom(
 *     date, // Use constructor from the given date
 *     date.getTime() // Use the date value to create a new date
 *   );
 * }
 */function D(t,e){if(typeof t==="function")return t(e);if(t&&typeof t==="object"&&S in t)return t[S](e);if(t instanceof Date)return new t.constructor(e);return new Date(e)}// Fallback for modularized imports:
/* export default */const L=/* unused pure expression or super */null&&D;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/toDate.js
/**
 * @name toDate
 * @category Common Helpers
 * @summary Convert the given argument to an instance of Date.
 *
 * @description
 * Convert the given argument to an instance of Date.
 *
 * If the argument is an instance of Date, the function returns its clone.
 *
 * If the argument is a number, it is treated as a timestamp.
 *
 * If the argument is none of the above, the function returns Invalid Date.
 *
 * Starting from v3.7.0, it clones a date using `[Symbol.for("constructDateFrom")]`
 * enabling to transfer extra properties from the reference date to the new date.
 * It's useful for extensions like [`TZDate`](https://github.com/date-fns/tz)
 * that accept a time zone as a constructor argument.
 *
 * **Note**: *all* Date arguments passed to any *date-fns* function is processed by `toDate`.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type, it is the type returned from the context function if it is passed, or inferred from the arguments.
 *
 * @param argument - The value to convert
 *
 * @returns The parsed date in the local time zone
 *
 * @example
 * // Clone the date:
 * const result = toDate(new Date(2014, 1, 11, 11, 30, 30))
 * //=> Tue Feb 11 2014 11:30:30
 *
 * @example
 * // Convert the timestamp to date:
 * const result = toDate(1392098430000)
 * //=> Tue Feb 11 2014 11:30:30
 */function W(t,e){// [TODO] Get rid of `toDate` or `constructFrom`?
return D(e||t,t)}// Fallback for modularized imports:
/* export default */const T=/* unused pure expression or super */null&&W;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/addMinutes.js
/**
 * The {@link addMinutes} function options.
 *//**
 * @name addMinutes
 * @category Minute Helpers
 * @summary Add the specified number of minutes to the given date.
 *
 * @description
 * Add the specified number of minutes to the given date.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type, it is the type returned from the context function if it is passed, or inferred from the arguments.
 *
 * @param date - The date to be changed
 * @param amount - The amount of minutes to be added.
 * @param options - An object with options
 *
 * @returns The new date with the minutes added
 *
 * @example
 * // Add 30 minutes to 10 July 2014 12:00:00:
 * const result = addMinutes(new Date(2014, 6, 10, 12, 0), 30)
 * //=> Thu Jul 10 2014 12:30:00
 */function E(t,e,n){const r=W(t,n?.in);r.setTime(r.getTime()+e*u);return r}// Fallback for modularized imports:
/* export default */const q=/* unused pure expression or super */null&&E;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/en-US/_lib/formatDistance.js
const C={lessThanXSeconds:{one:"less than a second",other:"less than {{count}} seconds"},xSeconds:{one:"1 second",other:"{{count}} seconds"},halfAMinute:"half a minute",lessThanXMinutes:{one:"less than a minute",other:"less than {{count}} minutes"},xMinutes:{one:"1 minute",other:"{{count}} minutes"},aboutXHours:{one:"about 1 hour",other:"about {{count}} hours"},xHours:{one:"1 hour",other:"{{count}} hours"},xDays:{one:"1 day",other:"{{count}} days"},aboutXWeeks:{one:"about 1 week",other:"about {{count}} weeks"},xWeeks:{one:"1 week",other:"{{count}} weeks"},aboutXMonths:{one:"about 1 month",other:"about {{count}} months"},xMonths:{one:"1 month",other:"{{count}} months"},aboutXYears:{one:"about 1 year",other:"about {{count}} years"},xYears:{one:"1 year",other:"{{count}} years"},overXYears:{one:"over 1 year",other:"over {{count}} years"},almostXYears:{one:"almost 1 year",other:"almost {{count}} years"}};const O=(t,e,n)=>{let r;const o=C[t];if(typeof o==="string"){r=o}else if(e===1){r=o.one}else{r=o.other.replace("{{count}}",e.toString())}if(n?.addSuffix){if(n.comparison&&n.comparison>0){return"in "+r}else{return r+" ago"}}return r};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/_lib/buildFormatLongFn.js
function Y(t){return (e={})=>{// TODO: Remove String()
const n=e.width?String(e.width):t.defaultWidth;const r=t.formats[n]||t.formats[t.defaultWidth];return r}};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/en-US/_lib/formatLong.js
const j={full:"EEEE, MMMM do, y",long:"MMMM do, y",medium:"MMM d, y",short:"MM/dd/yyyy"};const F={full:"h:mm:ss a zzzz",long:"h:mm:ss a z",medium:"h:mm:ss a",short:"h:mm a"};const H={full:"{{date}} 'at' {{time}}",long:"{{date}} 'at' {{time}}",medium:"{{date}}, {{time}}",short:"{{date}}, {{time}}"};const A={date:Y({formats:j,defaultWidth:"full"}),time:Y({formats:F,defaultWidth:"full"}),dateTime:Y({formats:H,defaultWidth:"full"})};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/en-US/_lib/formatRelative.js
const z={lastWeek:"'last' eeee 'at' p",yesterday:"'yesterday at' p",today:"'today at' p",tomorrow:"'tomorrow at' p",nextWeek:"eeee 'at' p",other:"P"};const N=(t,e,n,r)=>z[t];// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/_lib/buildLocalizeFn.js
/**
 * The localize function argument callback which allows to convert raw value to
 * the actual type.
 *
 * @param value - The value to convert
 *
 * @returns The converted value
 *//**
 * The map of localized values for each width.
 *//**
 * The index type of the locale unit value. It types conversion of units of
 * values that don't start at 0 (i.e. quarters).
 *//**
 * Converts the unit value to the tuple of values.
 *//**
 * The tuple of localized era values. The first element represents BC,
 * the second element represents AD.
 *//**
 * The tuple of localized quarter values. The first element represents Q1.
 *//**
 * The tuple of localized day values. The first element represents Sunday.
 *//**
 * The tuple of localized month values. The first element represents January.
 */function Q(t){return(e,n)=>{const r=n?.context?String(n.context):"standalone";let o;if(r==="formatting"&&t.formattingValues){const e=t.defaultFormattingWidth||t.defaultWidth;const r=n?.width?String(n.width):e;o=t.formattingValues[r]||t.formattingValues[e]}else{const e=t.defaultWidth;const r=n?.width?String(n.width):t.defaultWidth;o=t.values[r]||t.values[e]}const a=t.argumentCallback?t.argumentCallback(e):e;// @ts-expect-error - For some reason TypeScript just don't want to match it, no matter how hard we try. I challenge you to try to remove it!
return o[a]}};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/en-US/_lib/localize.js
const B={narrow:["B","A"],abbreviated:["BC","AD"],wide:["Before Christ","Anno Domini"]};const X={narrow:["1","2","3","4"],abbreviated:["Q1","Q2","Q3","Q4"],wide:["1st quarter","2nd quarter","3rd quarter","4th quarter"]};// Note: in English, the names of days of the week and months are capitalized.
// If you are making a new locale based on this one, check if the same is true for the language you're working on.
// Generally, formatted dates should look like they are in the middle of a sentence,
// e.g. in Spanish language the weekdays and months should be in the lowercase.
const G={narrow:["J","F","M","A","M","J","J","A","S","O","N","D"],abbreviated:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],wide:["January","February","March","April","May","June","July","August","September","October","November","December"]};const I={narrow:["S","M","T","W","T","F","S"],short:["Su","Mo","Tu","We","Th","Fr","Sa"],abbreviated:["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],wide:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"]};const $={narrow:{am:"a",pm:"p",midnight:"mi",noon:"n",morning:"morning",afternoon:"afternoon",evening:"evening",night:"night"},abbreviated:{am:"AM",pm:"PM",midnight:"midnight",noon:"noon",morning:"morning",afternoon:"afternoon",evening:"evening",night:"night"},wide:{am:"a.m.",pm:"p.m.",midnight:"midnight",noon:"noon",morning:"morning",afternoon:"afternoon",evening:"evening",night:"night"}};const J={narrow:{am:"a",pm:"p",midnight:"mi",noon:"n",morning:"in the morning",afternoon:"in the afternoon",evening:"in the evening",night:"at night"},abbreviated:{am:"AM",pm:"PM",midnight:"midnight",noon:"noon",morning:"in the morning",afternoon:"in the afternoon",evening:"in the evening",night:"at night"},wide:{am:"a.m.",pm:"p.m.",midnight:"midnight",noon:"noon",morning:"in the morning",afternoon:"in the afternoon",evening:"in the evening",night:"at night"}};const R=(t,e)=>{const n=Number(t);// If ordinal numbers depend on context, for example,
// if they are different for different grammatical genders,
// use `options.unit`.
//
// `unit` can be 'year', 'quarter', 'month', 'week', 'date', 'dayOfYear',
// 'day', 'hour', 'minute', 'second'.
const r=n%100;if(r>20||r<10){switch(r%10){case 1:return n+"st";case 2:return n+"nd";case 3:return n+"rd"}}return n+"th"};const U={ordinalNumber:R,era:Q({values:B,defaultWidth:"wide"}),quarter:Q({values:X,defaultWidth:"wide",argumentCallback:t=>t-1}),month:Q({values:G,defaultWidth:"wide"}),day:Q({values:I,defaultWidth:"wide"}),dayPeriod:Q({values:$,defaultWidth:"wide",formattingValues:J,defaultFormattingWidth:"wide"})};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/_lib/buildMatchFn.js
function Z(t){return(e,n={})=>{const r=n.width;const o=r&&t.matchPatterns[r]||t.matchPatterns[t.defaultMatchWidth];const a=e.match(o);if(!a){return null}const i=a[0];const s=r&&t.parsePatterns[r]||t.parsePatterns[t.defaultParseWidth];const c=Array.isArray(s)?K(s,t=>t.test(i)):V(s,t=>t.test(i));let u;u=t.valueCallback?t.valueCallback(c):c;u=n.valueCallback?n.valueCallback(u):u;const d=e.slice(i.length);return{value:u,rest:d}}}function V(t,e){for(const n in t){if(Object.prototype.hasOwnProperty.call(t,n)&&e(t[n])){return n}}return undefined}function K(t,e){for(let n=0;n<t.length;n++){if(e(t[n])){return n}}return undefined};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/_lib/buildMatchPatternFn.js
function tt(t){return(e,n={})=>{const r=e.match(t.matchPattern);if(!r)return null;const o=r[0];const a=e.match(t.parsePattern);if(!a)return null;let i=t.valueCallback?t.valueCallback(a[0]):a[0];// [TODO] I challenge you to fix the type
i=n.valueCallback?n.valueCallback(i):i;const s=e.slice(o.length);return{value:i,rest:s}}};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/en-US/_lib/match.js
const te=/^(\d+)(th|st|nd|rd)?/i;const tn=/\d+/i;const tr={narrow:/^(b|a)/i,abbreviated:/^(b\.?\s?c\.?|b\.?\s?c\.?\s?e\.?|a\.?\s?d\.?|c\.?\s?e\.?)/i,wide:/^(before christ|before common era|anno domini|common era)/i};const to={any:[/^b/i,/^(a|c)/i]};const ta={narrow:/^[1234]/i,abbreviated:/^q[1234]/i,wide:/^[1234](th|st|nd|rd)? quarter/i};const ti={any:[/1/i,/2/i,/3/i,/4/i]};const ts={narrow:/^[jfmasond]/i,abbreviated:/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)/i,wide:/^(january|february|march|april|may|june|july|august|september|october|november|december)/i};const tc={narrow:[/^j/i,/^f/i,/^m/i,/^a/i,/^m/i,/^j/i,/^j/i,/^a/i,/^s/i,/^o/i,/^n/i,/^d/i],any:[/^ja/i,/^f/i,/^mar/i,/^ap/i,/^may/i,/^jun/i,/^jul/i,/^au/i,/^s/i,/^o/i,/^n/i,/^d/i]};const tu={narrow:/^[smtwf]/i,short:/^(su|mo|tu|we|th|fr|sa)/i,abbreviated:/^(sun|mon|tue|wed|thu|fri|sat)/i,wide:/^(sunday|monday|tuesday|wednesday|thursday|friday|saturday)/i};const td={narrow:[/^s/i,/^m/i,/^t/i,/^w/i,/^t/i,/^f/i,/^s/i],any:[/^su/i,/^m/i,/^tu/i,/^w/i,/^th/i,/^f/i,/^sa/i]};const tl={narrow:/^(a|p|mi|n|(in the|at) (morning|afternoon|evening|night))/i,any:/^([ap]\.?\s?m\.?|midnight|noon|(in the|at) (morning|afternoon|evening|night))/i};const tf={any:{am:/^a/i,pm:/^p/i,midnight:/^mi/i,noon:/^no/i,morning:/morning/i,afternoon:/afternoon/i,evening:/evening/i,night:/night/i}};const th={ordinalNumber:tt({matchPattern:te,parsePattern:tn,valueCallback:t=>parseInt(t,10)}),era:Z({matchPatterns:tr,defaultMatchWidth:"wide",parsePatterns:to,defaultParseWidth:"any"}),quarter:Z({matchPatterns:ta,defaultMatchWidth:"wide",parsePatterns:ti,defaultParseWidth:"any",valueCallback:t=>t+1}),month:Z({matchPatterns:ts,defaultMatchWidth:"wide",parsePatterns:tc,defaultParseWidth:"any"}),day:Z({matchPatterns:tu,defaultMatchWidth:"wide",parsePatterns:td,defaultParseWidth:"any"}),dayPeriod:Z({matchPatterns:tl,defaultMatchWidth:"any",parsePatterns:tf,defaultParseWidth:"any"})};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/locale/en-US.js
/**
 * @category Locales
 * @summary English locale (United States).
 * @language English
 * @iso-639-2 eng
 * @author Sasha Koss [@kossnocorp](https://github.com/kossnocorp)
 * @author Lesha Koss [@leshakoss](https://github.com/leshakoss)
 */const tm={code:"en-US",formatDistance:O,formatLong:A,formatRelative:N,localize:U,match:th,options:{weekStartsOn:0/* Sunday */,firstWeekContainsDate:1}};// Fallback for modularized imports:
/* export default */const tg=/* unused pure expression or super */null&&tm;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/defaultOptions.js
let tw={};function tv(){return tw}function ty(t){tw=t};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/getTimezoneOffsetInMilliseconds.js
/**
 * Google Chrome as of 67.0.3396.87 introduced timezones with offset that includes seconds.
 * They usually appear for dates that denote time before the timezones were introduced
 * (e.g. for 'Europe/Prague' timezone the offset is GMT+00:57:44 before 1 October 1891
 * and GMT+01:00:00 after that date)
 *
 * Date#getTimezoneOffset returns the offset in minutes and would return 57 for the example above,
 * which would lead to incorrect calculations.
 *
 * This function returns the timezone offset in milliseconds that takes seconds in account.
 */function tb(t){const e=W(t);const n=new Date(Date.UTC(e.getFullYear(),e.getMonth(),e.getDate(),e.getHours(),e.getMinutes(),e.getSeconds(),e.getMilliseconds()));n.setUTCFullYear(e.getFullYear());return+t-+n};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/normalizeDates.js
function tp(t,...e){const n=D.bind(null,t||e.find(t=>typeof t==="object"));return e.map(n)};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/startOfDay.js
/**
 * The {@link startOfDay} function options.
 *//**
 * @name startOfDay
 * @category Day Helpers
 * @summary Return the start of a day for the given date.
 *
 * @description
 * Return the start of a day for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type, it is the type returned from the context function if it is passed, or inferred from the arguments.
 *
 * @param date - The original date
 * @param options - The options
 *
 * @returns The start of a day
 *
 * @example
 * // The start of a day for 2 September 2014 11:55:00:
 * const result = startOfDay(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Tue Sep 02 2014 00:00:00
 */function tM(t,e){const n=W(t,e?.in);n.setHours(0,0,0,0);return n}// Fallback for modularized imports:
/* export default */const t_=/* unused pure expression or super */null&&tM;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/differenceInCalendarDays.js
/**
 * The {@link differenceInCalendarDays} function options.
 *//**
 * @name differenceInCalendarDays
 * @category Day Helpers
 * @summary Get the number of calendar days between the given dates.
 *
 * @description
 * Get the number of calendar days between the given dates. This means that the times are removed
 * from the dates and then the difference in days is calculated.
 *
 * @param laterDate - The later date
 * @param earlierDate - The earlier date
 * @param options - The options object
 *
 * @returns The number of calendar days
 *
 * @example
 * // How many calendar days are between
 * // 2 July 2011 23:00:00 and 2 July 2012 00:00:00?
 * const result = differenceInCalendarDays(
 *   new Date(2012, 6, 2, 0, 0),
 *   new Date(2011, 6, 2, 23, 0)
 * )
 * //=> 366
 * // How many calendar days are between
 * // 2 July 2011 23:59:00 and 3 July 2011 00:01:00?
 * const result = differenceInCalendarDays(
 *   new Date(2011, 6, 3, 0, 1),
 *   new Date(2011, 6, 2, 23, 59)
 * )
 * //=> 1
 */function tx(t,e,n){const[r,o]=tp(n?.in,t,e);const a=tM(r);const i=tM(o);const s=+a-tb(a);const u=+i-tb(i);// Round the number of days to the nearest integer because the number of
// milliseconds in a day is not constant (e.g. it's different in the week of
// the daylight saving time clock shift).
return Math.round((s-u)/c)}// Fallback for modularized imports:
/* export default */const tk=/* unused pure expression or super */null&&tx;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/startOfYear.js
/**
 * The {@link startOfYear} function options.
 *//**
 * @name startOfYear
 * @category Year Helpers
 * @summary Return the start of a year for the given date.
 *
 * @description
 * Return the start of a year for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type, it is the type returned from the context function if it is passed, or inferred from the arguments.
 *
 * @param date - The original date
 * @param options - The options
 *
 * @returns The start of a year
 *
 * @example
 * // The start of a year for 2 September 2014 11:55:00:
 * const result = startOfYear(new Date(2014, 8, 2, 11, 55, 00))
 * //=> Wed Jan 01 2014 00:00:00
 */function tP(t,e){const n=W(t,e?.in);n.setFullYear(n.getFullYear(),0,1);n.setHours(0,0,0,0);return n}// Fallback for modularized imports:
/* export default */const tS=/* unused pure expression or super */null&&tP;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/getDayOfYear.js
/**
 * The {@link getDayOfYear} function options.
 *//**
 * @name getDayOfYear
 * @category Day Helpers
 * @summary Get the day of the year of the given date.
 *
 * @description
 * Get the day of the year of the given date.
 *
 * @param date - The given date
 * @param options - The options
 *
 * @returns The day of year
 *
 * @example
 * // Which day of the year is 2 July 2014?
 * const result = getDayOfYear(new Date(2014, 6, 2))
 * //=> 183
 */function tD(t,e){const n=W(t,e?.in);const r=tx(n,tP(n));const o=r+1;return o}// Fallback for modularized imports:
/* export default */const tL=/* unused pure expression or super */null&&tD;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/startOfWeek.js
/**
 * The {@link startOfWeek} function options.
 *//**
 * @name startOfWeek
 * @category Week Helpers
 * @summary Return the start of a week for the given date.
 *
 * @description
 * Return the start of a week for the given date.
 * The result will be in the local timezone.
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type, it is the type returned from the context function if it is passed, or inferred from the arguments.
 *
 * @param date - The original date
 * @param options - An object with options
 *
 * @returns The start of a week
 *
 * @example
 * // The start of a week for 2 September 2014 11:55:00:
 * const result = startOfWeek(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Sun Aug 31 2014 00:00:00
 *
 * @example
 * // If the week starts on Monday, the start of the week for 2 September 2014 11:55:00:
 * const result = startOfWeek(new Date(2014, 8, 2, 11, 55, 0), { weekStartsOn: 1 })
 * //=> Mon Sep 01 2014 00:00:00
 */function tW(t,e){const n=tv();const r=e?.weekStartsOn??e?.locale?.options?.weekStartsOn??n.weekStartsOn??n.locale?.options?.weekStartsOn??0;const o=W(t,e?.in);const a=o.getDay();const i=(a<r?7:0)+a-r;o.setDate(o.getDate()-i);o.setHours(0,0,0,0);return o}// Fallback for modularized imports:
/* export default */const tT=/* unused pure expression or super */null&&tW;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/startOfISOWeek.js
/**
 * The {@link startOfISOWeek} function options.
 *//**
 * @name startOfISOWeek
 * @category ISO Week Helpers
 * @summary Return the start of an ISO week for the given date.
 *
 * @description
 * Return the start of an ISO week for the given date.
 * The result will be in the local timezone.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type, it is the type returned from the context function if it is passed, or inferred from the arguments.
 *
 * @param date - The original date
 * @param options - An object with options
 *
 * @returns The start of an ISO week
 *
 * @example
 * // The start of an ISO week for 2 September 2014 11:55:00:
 * const result = startOfISOWeek(new Date(2014, 8, 2, 11, 55, 0))
 * //=> Mon Sep 01 2014 00:00:00
 */function tE(t,e){return tW(t,{...e,weekStartsOn:1})}// Fallback for modularized imports:
/* export default */const tq=/* unused pure expression or super */null&&tE;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/getISOWeekYear.js
/**
 * The {@link getISOWeekYear} function options.
 *//**
 * @name getISOWeekYear
 * @category ISO Week-Numbering Year Helpers
 * @summary Get the ISO week-numbering year of the given date.
 *
 * @description
 * Get the ISO week-numbering year of the given date,
 * which always starts 3 days before the year's first Thursday.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @param date - The given date
 *
 * @returns The ISO week-numbering year
 *
 * @example
 * // Which ISO-week numbering year is 2 January 2005?
 * const result = getISOWeekYear(new Date(2005, 0, 2))
 * //=> 2004
 */function tC(t,e){const n=W(t,e?.in);const r=n.getFullYear();const o=D(n,0);o.setFullYear(r+1,0,4);o.setHours(0,0,0,0);const a=tE(o);const i=D(n,0);i.setFullYear(r,0,4);i.setHours(0,0,0,0);const s=tE(i);if(n.getTime()>=a.getTime()){return r+1}else if(n.getTime()>=s.getTime()){return r}else{return r-1}}// Fallback for modularized imports:
/* export default */const tO=/* unused pure expression or super */null&&tC;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/startOfISOWeekYear.js
/**
 * The {@link startOfISOWeekYear} function options.
 *//**
 * @name startOfISOWeekYear
 * @category ISO Week-Numbering Year Helpers
 * @summary Return the start of an ISO week-numbering year for the given date.
 *
 * @description
 * Return the start of an ISO week-numbering year,
 * which always starts 3 days before the year's first Thursday.
 * The result will be in the local timezone.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type, it is the type returned from the context function if it is passed, or inferred from the arguments.
 *
 * @param date - The original date
 * @param options - An object with options
 *
 * @returns The start of an ISO week-numbering year
 *
 * @example
 * // The start of an ISO week-numbering year for 2 July 2005:
 * const result = startOfISOWeekYear(new Date(2005, 6, 2))
 * //=> Mon Jan 03 2005 00:00:00
 */function tY(t,e){const n=tC(t,e);const r=D(e?.in||t,0);r.setFullYear(n,0,4);r.setHours(0,0,0,0);return tE(r)}// Fallback for modularized imports:
/* export default */const tj=/* unused pure expression or super */null&&tY;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/getISOWeek.js
/**
 * The {@link getISOWeek} function options.
 *//**
 * @name getISOWeek
 * @category ISO Week Helpers
 * @summary Get the ISO week of the given date.
 *
 * @description
 * Get the ISO week of the given date.
 *
 * ISO week-numbering year: http://en.wikipedia.org/wiki/ISO_week_date
 *
 * @param date - The given date
 * @param options - The options
 *
 * @returns The ISO week
 *
 * @example
 * // Which week of the ISO-week numbering year is 2 January 2005?
 * const result = getISOWeek(new Date(2005, 0, 2))
 * //=> 53
 */function tF(t,e){const n=W(t,e?.in);const r=+tE(n)-+tY(n);// Round the number of weeks to the nearest integer because the number of
// milliseconds in a week is not constant (e.g. it's different in the week of
// the daylight saving time clock shift).
return Math.round(r/s)+1}// Fallback for modularized imports:
/* export default */const tH=/* unused pure expression or super */null&&tF;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/getWeekYear.js
/**
 * The {@link getWeekYear} function options.
 *//**
 * @name getWeekYear
 * @category Week-Numbering Year Helpers
 * @summary Get the local week-numbering year of the given date.
 *
 * @description
 * Get the local week-numbering year of the given date.
 * The exact calculation depends on the values of
 * `options.weekStartsOn` (which is the index of the first day of the week)
 * and `options.firstWeekContainsDate` (which is the day of January, which is always in
 * the first week of the week-numbering year)
 *
 * Week numbering: https://en.wikipedia.org/wiki/Week#The_ISO_week_date_system
 *
 * @param date - The given date
 * @param options - An object with options.
 *
 * @returns The local week-numbering year
 *
 * @example
 * // Which week numbering year is 26 December 2004 with the default settings?
 * const result = getWeekYear(new Date(2004, 11, 26))
 * //=> 2005
 *
 * @example
 * // Which week numbering year is 26 December 2004 if week starts on Saturday?
 * const result = getWeekYear(new Date(2004, 11, 26), { weekStartsOn: 6 })
 * //=> 2004
 *
 * @example
 * // Which week numbering year is 26 December 2004 if the first week contains 4 January?
 * const result = getWeekYear(new Date(2004, 11, 26), { firstWeekContainsDate: 4 })
 * //=> 2004
 */function tA(t,e){const n=W(t,e?.in);const r=n.getFullYear();const o=tv();const a=e?.firstWeekContainsDate??e?.locale?.options?.firstWeekContainsDate??o.firstWeekContainsDate??o.locale?.options?.firstWeekContainsDate??1;const i=D(e?.in||t,0);i.setFullYear(r+1,0,a);i.setHours(0,0,0,0);const s=tW(i,e);const c=D(e?.in||t,0);c.setFullYear(r,0,a);c.setHours(0,0,0,0);const u=tW(c,e);if(+n>=+s){return r+1}else if(+n>=+u){return r}else{return r-1}}// Fallback for modularized imports:
/* export default */const tz=/* unused pure expression or super */null&&tA;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/startOfWeekYear.js
/**
 * The {@link startOfWeekYear} function options.
 *//**
 * @name startOfWeekYear
 * @category Week-Numbering Year Helpers
 * @summary Return the start of a local week-numbering year for the given date.
 *
 * @description
 * Return the start of a local week-numbering year.
 * The exact calculation depends on the values of
 * `options.weekStartsOn` (which is the index of the first day of the week)
 * and `options.firstWeekContainsDate` (which is the day of January, which is always in
 * the first week of the week-numbering year)
 *
 * Week numbering: https://en.wikipedia.org/wiki/Week#The_ISO_week_date_system
 *
 * @typeParam DateType - The `Date` type, the function operates on. Gets inferred from passed arguments. Allows to use extensions like [`UTCDate`](https://github.com/date-fns/utc).
 * @typeParam ResultDate - The result `Date` type.
 *
 * @param date - The original date
 * @param options - An object with options
 *
 * @returns The start of a week-numbering year
 *
 * @example
 * // The start of an a week-numbering year for 2 July 2005 with default settings:
 * const result = startOfWeekYear(new Date(2005, 6, 2))
 * //=> Sun Dec 26 2004 00:00:00
 *
 * @example
 * // The start of a week-numbering year for 2 July 2005
 * // if Monday is the first day of week
 * // and 4 January is always in the first week of the year:
 * const result = startOfWeekYear(new Date(2005, 6, 2), {
 *   weekStartsOn: 1,
 *   firstWeekContainsDate: 4
 * })
 * //=> Mon Jan 03 2005 00:00:00
 */function tN(t,e){const n=tv();const r=e?.firstWeekContainsDate??e?.locale?.options?.firstWeekContainsDate??n.firstWeekContainsDate??n.locale?.options?.firstWeekContainsDate??1;const o=tA(t,e);const a=D(e?.in||t,0);a.setFullYear(o,0,r);a.setHours(0,0,0,0);const i=tW(a,e);return i}// Fallback for modularized imports:
/* export default */const tQ=/* unused pure expression or super */null&&tN;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/getWeek.js
/**
 * The {@link getWeek} function options.
 *//**
 * @name getWeek
 * @category Week Helpers
 * @summary Get the local week index of the given date.
 *
 * @description
 * Get the local week index of the given date.
 * The exact calculation depends on the values of
 * `options.weekStartsOn` (which is the index of the first day of the week)
 * and `options.firstWeekContainsDate` (which is the day of January, which is always in
 * the first week of the week-numbering year)
 *
 * Week numbering: https://en.wikipedia.org/wiki/Week#The_ISO_week_date_system
 *
 * @param date - The given date
 * @param options - An object with options
 *
 * @returns The week
 *
 * @example
 * // Which week of the local week numbering year is 2 January 2005 with default options?
 * const result = getWeek(new Date(2005, 0, 2))
 * //=> 2
 *
 * @example
 * // Which week of the local week numbering year is 2 January 2005,
 * // if Monday is the first day of the week,
 * // and the first week of the year always contains 4 January?
 * const result = getWeek(new Date(2005, 0, 2), {
 *   weekStartsOn: 1,
 *   firstWeekContainsDate: 4
 * })
 * //=> 53
 */function tB(t,e){const n=W(t,e?.in);const r=+tW(n,e)-+tN(n,e);// Round the number of weeks to the nearest integer because the number of
// milliseconds in a week is not constant (e.g. it's different in the week of
// the daylight saving time clock shift).
return Math.round(r/s)+1}// Fallback for modularized imports:
/* export default */const tX=/* unused pure expression or super */null&&tB;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/addLeadingZeros.js
function tG(t,e){const n=t<0?"-":"";const r=Math.abs(t).toString().padStart(e,"0");return n+r};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/format/lightFormatters.js
/*
 * |     | Unit                           |     | Unit                           |
 * |-----|--------------------------------|-----|--------------------------------|
 * |  a  | AM, PM                         |  A* |                                |
 * |  d  | Day of month                   |  D  |                                |
 * |  h  | Hour [1-12]                    |  H  | Hour [0-23]                    |
 * |  m  | Minute                         |  M  | Month                          |
 * |  s  | Second                         |  S  | Fraction of second             |
 * |  y  | Year (abs)                     |  Y  |                                |
 *
 * Letters marked by * are not implemented but reserved by Unicode standard.
 */const tI={// Year
y(t,e){// From http://www.unicode.org/reports/tr35/tr35-31/tr35-dates.html#Date_Format_tokens
// | Year     |     y | yy |   yyy |  yyyy | yyyyy |
// |----------|-------|----|-------|-------|-------|
// | AD 1     |     1 | 01 |   001 |  0001 | 00001 |
// | AD 12    |    12 | 12 |   012 |  0012 | 00012 |
// | AD 123   |   123 | 23 |   123 |  0123 | 00123 |
// | AD 1234  |  1234 | 34 |  1234 |  1234 | 01234 |
// | AD 12345 | 12345 | 45 | 12345 | 12345 | 12345 |
const n=t.getFullYear();// Returns 1 for 1 BC (which is year 0 in JavaScript)
const r=n>0?n:1-n;return tG(e==="yy"?r%100:r,e.length)},// Month
M(t,e){const n=t.getMonth();return e==="M"?String(n+1):tG(n+1,2)},// Day of the month
d(t,e){return tG(t.getDate(),e.length)},// AM or PM
a(t,e){const n=t.getHours()/12>=1?"pm":"am";switch(e){case"a":case"aa":return n.toUpperCase();case"aaa":return n;case"aaaaa":return n[0];case"aaaa":default:return n==="am"?"a.m.":"p.m."}},// Hour [1-12]
h(t,e){return tG(t.getHours()%12||12,e.length)},// Hour [0-23]
H(t,e){return tG(t.getHours(),e.length)},// Minute
m(t,e){return tG(t.getMinutes(),e.length)},// Second
s(t,e){return tG(t.getSeconds(),e.length)},// Fraction of second
S(t,e){const n=e.length;const r=t.getMilliseconds();const o=Math.trunc(r*Math.pow(10,n-3));return tG(o,e.length)}};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/format/formatters.js
const t$={am:"am",pm:"pm",midnight:"midnight",noon:"noon",morning:"morning",afternoon:"afternoon",evening:"evening",night:"night"};/*
 * |     | Unit                           |     | Unit                           |
 * |-----|--------------------------------|-----|--------------------------------|
 * |  a  | AM, PM                         |  A* | Milliseconds in day            |
 * |  b  | AM, PM, noon, midnight         |  B  | Flexible day period            |
 * |  c  | Stand-alone local day of week  |  C* | Localized hour w/ day period   |
 * |  d  | Day of month                   |  D  | Day of year                    |
 * |  e  | Local day of week              |  E  | Day of week                    |
 * |  f  |                                |  F* | Day of week in month           |
 * |  g* | Modified Julian day            |  G  | Era                            |
 * |  h  | Hour [1-12]                    |  H  | Hour [0-23]                    |
 * |  i! | ISO day of week                |  I! | ISO week of year               |
 * |  j* | Localized hour w/ day period   |  J* | Localized hour w/o day period  |
 * |  k  | Hour [1-24]                    |  K  | Hour [0-11]                    |
 * |  l* | (deprecated)                   |  L  | Stand-alone month              |
 * |  m  | Minute                         |  M  | Month                          |
 * |  n  |                                |  N  |                                |
 * |  o! | Ordinal number modifier        |  O  | Timezone (GMT)                 |
 * |  p! | Long localized time            |  P! | Long localized date            |
 * |  q  | Stand-alone quarter            |  Q  | Quarter                        |
 * |  r* | Related Gregorian year         |  R! | ISO week-numbering year        |
 * |  s  | Second                         |  S  | Fraction of second             |
 * |  t! | Seconds timestamp              |  T! | Milliseconds timestamp         |
 * |  u  | Extended year                  |  U* | Cyclic year                    |
 * |  v* | Timezone (generic non-locat.)  |  V* | Timezone (location)            |
 * |  w  | Local week of year             |  W* | Week of month                  |
 * |  x  | Timezone (ISO-8601 w/o Z)      |  X  | Timezone (ISO-8601)            |
 * |  y  | Year (abs)                     |  Y  | Local week-numbering year      |
 * |  z  | Timezone (specific non-locat.) |  Z* | Timezone (aliases)             |
 *
 * Letters marked by * are not implemented but reserved by Unicode standard.
 *
 * Letters marked by ! are non-standard, but implemented by date-fns:
 * - `o` modifies the previous token to turn it into an ordinal (see `format` docs)
 * - `i` is ISO day of week. For `i` and `ii` is returns numeric ISO week days,
 *   i.e. 7 for Sunday, 1 for Monday, etc.
 * - `I` is ISO week of year, as opposed to `w` which is local week of year.
 * - `R` is ISO week-numbering year, as opposed to `Y` which is local week-numbering year.
 *   `R` is supposed to be used in conjunction with `I` and `i`
 *   for universal ISO week-numbering date, whereas
 *   `Y` is supposed to be used in conjunction with `w` and `e`
 *   for week-numbering date specific to the locale.
 * - `P` is long localized date format
 * - `p` is long localized time format
 */const tJ={// Era
G:function(t,e,n){const r=t.getFullYear()>0?1:0;switch(e){// AD, BC
case"G":case"GG":case"GGG":return n.era(r,{width:"abbreviated"});// A, B
case"GGGGG":return n.era(r,{width:"narrow"});// Anno Domini, Before Christ
case"GGGG":default:return n.era(r,{width:"wide"})}},// Year
y:function(t,e,n){// Ordinal number
if(e==="yo"){const e=t.getFullYear();// Returns 1 for 1 BC (which is year 0 in JavaScript)
const r=e>0?e:1-e;return n.ordinalNumber(r,{unit:"year"})}return tI.y(t,e)},// Local week-numbering year
Y:function(t,e,n,r){const o=tA(t,r);// Returns 1 for 1 BC (which is year 0 in JavaScript)
const a=o>0?o:1-o;// Two digit year
if(e==="YY"){const t=a%100;return tG(t,2)}// Ordinal number
if(e==="Yo"){return n.ordinalNumber(a,{unit:"year"})}// Padding
return tG(a,e.length)},// ISO week-numbering year
R:function(t,e){const n=tC(t);// Padding
return tG(n,e.length)},// Extended year. This is a single number designating the year of this calendar system.
// The main difference between `y` and `u` localizers are B.C. years:
// | Year | `y` | `u` |
// |------|-----|-----|
// | AC 1 |   1 |   1 |
// | BC 1 |   1 |   0 |
// | BC 2 |   2 |  -1 |
// Also `yy` always returns the last two digits of a year,
// while `uu` pads single digit years to 2 characters and returns other years unchanged.
u:function(t,e){const n=t.getFullYear();return tG(n,e.length)},// Quarter
Q:function(t,e,n){const r=Math.ceil((t.getMonth()+1)/3);switch(e){// 1, 2, 3, 4
case"Q":return String(r);// 01, 02, 03, 04
case"QQ":return tG(r,2);// 1st, 2nd, 3rd, 4th
case"Qo":return n.ordinalNumber(r,{unit:"quarter"});// Q1, Q2, Q3, Q4
case"QQQ":return n.quarter(r,{width:"abbreviated",context:"formatting"});// 1, 2, 3, 4 (narrow quarter; could be not numerical)
case"QQQQQ":return n.quarter(r,{width:"narrow",context:"formatting"});// 1st quarter, 2nd quarter, ...
case"QQQQ":default:return n.quarter(r,{width:"wide",context:"formatting"})}},// Stand-alone quarter
q:function(t,e,n){const r=Math.ceil((t.getMonth()+1)/3);switch(e){// 1, 2, 3, 4
case"q":return String(r);// 01, 02, 03, 04
case"qq":return tG(r,2);// 1st, 2nd, 3rd, 4th
case"qo":return n.ordinalNumber(r,{unit:"quarter"});// Q1, Q2, Q3, Q4
case"qqq":return n.quarter(r,{width:"abbreviated",context:"standalone"});// 1, 2, 3, 4 (narrow quarter; could be not numerical)
case"qqqqq":return n.quarter(r,{width:"narrow",context:"standalone"});// 1st quarter, 2nd quarter, ...
case"qqqq":default:return n.quarter(r,{width:"wide",context:"standalone"})}},// Month
M:function(t,e,n){const r=t.getMonth();switch(e){case"M":case"MM":return tI.M(t,e);// 1st, 2nd, ..., 12th
case"Mo":return n.ordinalNumber(r+1,{unit:"month"});// Jan, Feb, ..., Dec
case"MMM":return n.month(r,{width:"abbreviated",context:"formatting"});// J, F, ..., D
case"MMMMM":return n.month(r,{width:"narrow",context:"formatting"});// January, February, ..., December
case"MMMM":default:return n.month(r,{width:"wide",context:"formatting"})}},// Stand-alone month
L:function(t,e,n){const r=t.getMonth();switch(e){// 1, 2, ..., 12
case"L":return String(r+1);// 01, 02, ..., 12
case"LL":return tG(r+1,2);// 1st, 2nd, ..., 12th
case"Lo":return n.ordinalNumber(r+1,{unit:"month"});// Jan, Feb, ..., Dec
case"LLL":return n.month(r,{width:"abbreviated",context:"standalone"});// J, F, ..., D
case"LLLLL":return n.month(r,{width:"narrow",context:"standalone"});// January, February, ..., December
case"LLLL":default:return n.month(r,{width:"wide",context:"standalone"})}},// Local week of year
w:function(t,e,n,r){const o=tB(t,r);if(e==="wo"){return n.ordinalNumber(o,{unit:"week"})}return tG(o,e.length)},// ISO week of year
I:function(t,e,n){const r=tF(t);if(e==="Io"){return n.ordinalNumber(r,{unit:"week"})}return tG(r,e.length)},// Day of the month
d:function(t,e,n){if(e==="do"){return n.ordinalNumber(t.getDate(),{unit:"date"})}return tI.d(t,e)},// Day of year
D:function(t,e,n){const r=tD(t);if(e==="Do"){return n.ordinalNumber(r,{unit:"dayOfYear"})}return tG(r,e.length)},// Day of week
E:function(t,e,n){const r=t.getDay();switch(e){// Tue
case"E":case"EE":case"EEE":return n.day(r,{width:"abbreviated",context:"formatting"});// T
case"EEEEE":return n.day(r,{width:"narrow",context:"formatting"});// Tu
case"EEEEEE":return n.day(r,{width:"short",context:"formatting"});// Tuesday
case"EEEE":default:return n.day(r,{width:"wide",context:"formatting"})}},// Local day of week
e:function(t,e,n,r){const o=t.getDay();const a=(o-r.weekStartsOn+8)%7||7;switch(e){// Numerical value (Nth day of week with current locale or weekStartsOn)
case"e":return String(a);// Padded numerical value
case"ee":return tG(a,2);// 1st, 2nd, ..., 7th
case"eo":return n.ordinalNumber(a,{unit:"day"});case"eee":return n.day(o,{width:"abbreviated",context:"formatting"});// T
case"eeeee":return n.day(o,{width:"narrow",context:"formatting"});// Tu
case"eeeeee":return n.day(o,{width:"short",context:"formatting"});// Tuesday
case"eeee":default:return n.day(o,{width:"wide",context:"formatting"})}},// Stand-alone local day of week
c:function(t,e,n,r){const o=t.getDay();const a=(o-r.weekStartsOn+8)%7||7;switch(e){// Numerical value (same as in `e`)
case"c":return String(a);// Padded numerical value
case"cc":return tG(a,e.length);// 1st, 2nd, ..., 7th
case"co":return n.ordinalNumber(a,{unit:"day"});case"ccc":return n.day(o,{width:"abbreviated",context:"standalone"});// T
case"ccccc":return n.day(o,{width:"narrow",context:"standalone"});// Tu
case"cccccc":return n.day(o,{width:"short",context:"standalone"});// Tuesday
case"cccc":default:return n.day(o,{width:"wide",context:"standalone"})}},// ISO day of week
i:function(t,e,n){const r=t.getDay();const o=r===0?7:r;switch(e){// 2
case"i":return String(o);// 02
case"ii":return tG(o,e.length);// 2nd
case"io":return n.ordinalNumber(o,{unit:"day"});// Tue
case"iii":return n.day(r,{width:"abbreviated",context:"formatting"});// T
case"iiiii":return n.day(r,{width:"narrow",context:"formatting"});// Tu
case"iiiiii":return n.day(r,{width:"short",context:"formatting"});// Tuesday
case"iiii":default:return n.day(r,{width:"wide",context:"formatting"})}},// AM or PM
a:function(t,e,n){const r=t.getHours();const o=r/12>=1?"pm":"am";switch(e){case"a":case"aa":return n.dayPeriod(o,{width:"abbreviated",context:"formatting"});case"aaa":return n.dayPeriod(o,{width:"abbreviated",context:"formatting"}).toLowerCase();case"aaaaa":return n.dayPeriod(o,{width:"narrow",context:"formatting"});case"aaaa":default:return n.dayPeriod(o,{width:"wide",context:"formatting"})}},// AM, PM, midnight, noon
b:function(t,e,n){const r=t.getHours();let o;if(r===12){o=t$.noon}else if(r===0){o=t$.midnight}else{o=r/12>=1?"pm":"am"}switch(e){case"b":case"bb":return n.dayPeriod(o,{width:"abbreviated",context:"formatting"});case"bbb":return n.dayPeriod(o,{width:"abbreviated",context:"formatting"}).toLowerCase();case"bbbbb":return n.dayPeriod(o,{width:"narrow",context:"formatting"});case"bbbb":default:return n.dayPeriod(o,{width:"wide",context:"formatting"})}},// in the morning, in the afternoon, in the evening, at night
B:function(t,e,n){const r=t.getHours();let o;if(r>=17){o=t$.evening}else if(r>=12){o=t$.afternoon}else if(r>=4){o=t$.morning}else{o=t$.night}switch(e){case"B":case"BB":case"BBB":return n.dayPeriod(o,{width:"abbreviated",context:"formatting"});case"BBBBB":return n.dayPeriod(o,{width:"narrow",context:"formatting"});case"BBBB":default:return n.dayPeriod(o,{width:"wide",context:"formatting"})}},// Hour [1-12]
h:function(t,e,n){if(e==="ho"){let e=t.getHours()%12;if(e===0)e=12;return n.ordinalNumber(e,{unit:"hour"})}return tI.h(t,e)},// Hour [0-23]
H:function(t,e,n){if(e==="Ho"){return n.ordinalNumber(t.getHours(),{unit:"hour"})}return tI.H(t,e)},// Hour [0-11]
K:function(t,e,n){const r=t.getHours()%12;if(e==="Ko"){return n.ordinalNumber(r,{unit:"hour"})}return tG(r,e.length)},// Hour [1-24]
k:function(t,e,n){let r=t.getHours();if(r===0)r=24;if(e==="ko"){return n.ordinalNumber(r,{unit:"hour"})}return tG(r,e.length)},// Minute
m:function(t,e,n){if(e==="mo"){return n.ordinalNumber(t.getMinutes(),{unit:"minute"})}return tI.m(t,e)},// Second
s:function(t,e,n){if(e==="so"){return n.ordinalNumber(t.getSeconds(),{unit:"second"})}return tI.s(t,e)},// Fraction of second
S:function(t,e){return tI.S(t,e)},// Timezone (ISO-8601. If offset is 0, output is always `'Z'`)
X:function(t,e,n){const r=t.getTimezoneOffset();if(r===0){return"Z"}switch(e){// Hours and optional minutes
case"X":return tU(r);// Hours, minutes and optional seconds without `:` delimiter
// Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
// so this token always has the same output as `XX`
case"XXXX":case"XX":return tZ(r);// Hours, minutes and optional seconds with `:` delimiter
// Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
// so this token always has the same output as `XXX`
case"XXXXX":case"XXX":default:return tZ(r,":")}},// Timezone (ISO-8601. If offset is 0, output is `'+00:00'` or equivalent)
x:function(t,e,n){const r=t.getTimezoneOffset();switch(e){// Hours and optional minutes
case"x":return tU(r);// Hours, minutes and optional seconds without `:` delimiter
// Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
// so this token always has the same output as `xx`
case"xxxx":case"xx":return tZ(r);// Hours, minutes and optional seconds with `:` delimiter
// Note: neither ISO-8601 nor JavaScript supports seconds in timezone offsets
// so this token always has the same output as `xxx`
case"xxxxx":case"xxx":default:return tZ(r,":")}},// Timezone (GMT)
O:function(t,e,n){const r=t.getTimezoneOffset();switch(e){// Short
case"O":case"OO":case"OOO":return"GMT"+tR(r,":");// Long
case"OOOO":default:return"GMT"+tZ(r,":")}},// Timezone (specific non-location)
z:function(t,e,n){const r=t.getTimezoneOffset();switch(e){// Short
case"z":case"zz":case"zzz":return"GMT"+tR(r,":");// Long
case"zzzz":default:return"GMT"+tZ(r,":")}},// Seconds timestamp
t:function(t,e,n){const r=Math.trunc(+t/1e3);return tG(r,e.length)},// Milliseconds timestamp
T:function(t,e,n){return tG(+t,e.length)}};function tR(t,e=""){const n=t>0?"-":"+";const r=Math.abs(t);const o=Math.trunc(r/60);const a=r%60;if(a===0){return n+String(o)}return n+String(o)+e+tG(a,2)}function tU(t,e){if(t%60===0){const e=t>0?"-":"+";return e+tG(Math.abs(t)/60,2)}return tZ(t,e)}function tZ(t,e=""){const n=t>0?"-":"+";const r=Math.abs(t);const o=tG(Math.trunc(r/60),2);const a=tG(r%60,2);return n+o+e+a};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/format/longFormatters.js
const tV=(t,e)=>{switch(t){case"P":return e.date({width:"short"});case"PP":return e.date({width:"medium"});case"PPP":return e.date({width:"long"});case"PPPP":default:return e.date({width:"full"})}};const tK=(t,e)=>{switch(t){case"p":return e.time({width:"short"});case"pp":return e.time({width:"medium"});case"ppp":return e.time({width:"long"});case"pppp":default:return e.time({width:"full"})}};const t0=(t,e)=>{const n=t.match(/(P+)(p+)?/)||[];const r=n[1];const o=n[2];if(!o){return tV(t,e)}let a;switch(r){case"P":a=e.dateTime({width:"short"});break;case"PP":a=e.dateTime({width:"medium"});break;case"PPP":a=e.dateTime({width:"long"});break;case"PPPP":default:a=e.dateTime({width:"full"});break}return a.replace("{{date}}",tV(r,e)).replace("{{time}}",tK(o,e))};const t1={p:tK,P:t0};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/_lib/protectedTokens.js
const t2=/^D+$/;const t3=/^Y+$/;const t6=["D","DD","YY","YYYY"];function t4(t){return t2.test(t)}function t5(t){return t3.test(t)}function t8(t,e,n){const r=t9(t,e,n);console.warn(r);if(t6.includes(t))throw new RangeError(r)}function t9(t,e,n){const r=t[0]==="Y"?"years":"days of the month";return`Use \`${t.toLowerCase()}\` instead of \`${t}\` (in \`${e}\`) for formatting ${r} to the input \`${n}\`; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md`};// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/isDate.js
/**
 * @name isDate
 * @category Common Helpers
 * @summary Is the given value a date?
 *
 * @description
 * Returns true if the given value is an instance of Date. The function works for dates transferred across iframes.
 *
 * @param value - The value to check
 *
 * @returns True if the given value is a date
 *
 * @example
 * // For a valid date:
 * const result = isDate(new Date())
 * //=> true
 *
 * @example
 * // For an invalid date:
 * const result = isDate(new Date(NaN))
 * //=> true
 *
 * @example
 * // For some value:
 * const result = isDate('2014-02-31')
 * //=> false
 *
 * @example
 * // For an object:
 * const result = isDate({})
 * //=> false
 */function t7(t){return t instanceof Date||typeof t==="object"&&Object.prototype.toString.call(t)==="[object Date]"}// Fallback for modularized imports:
/* export default */const et=/* unused pure expression or super */null&&t7;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/isValid.js
/**
 * @name isValid
 * @category Common Helpers
 * @summary Is the given date valid?
 *
 * @description
 * Returns false if argument is Invalid Date and true otherwise.
 * Argument is converted to Date using `toDate`. See [toDate](https://date-fns.org/docs/toDate)
 * Invalid Date is a Date, whose time value is NaN.
 *
 * Time value of Date: http://es5.github.io/#x15.9.1.1
 *
 * @param date - The date to check
 *
 * @returns The date is valid
 *
 * @example
 * // For the valid date:
 * const result = isValid(new Date(2014, 1, 31))
 * //=> true
 *
 * @example
 * // For the value, convertible into a date:
 * const result = isValid(1393804800000)
 * //=> true
 *
 * @example
 * // For the invalid date:
 * const result = isValid(new Date(''))
 * //=> false
 */function ee(t){return!(!t7(t)&&typeof t!=="number"||isNaN(+W(t)))}// Fallback for modularized imports:
/* export default */const en=/* unused pure expression or super */null&&ee;// CONCATENATED MODULE: ./node_modules/.pnpm/date-fns@4.1.0/node_modules/date-fns/format.js
// Rexports of internal for libraries to use.
// See: https://github.com/date-fns/date-fns/issues/3638#issuecomment-1877082874
// This RegExp consists of three parts separated by `|`:
// - [yYQqMLwIdDecihHKkms]o matches any available ordinal number token
//   (one of the certain letters followed by `o`)
// - (\w)\1* matches any sequences of the same letter
// - '' matches two quote characters in a row
// - '(''|[^'])+('|$) matches anything surrounded by two quote characters ('),
//   except a single quote symbol, which ends the sequence.
//   Two quote characters do not end the sequence.
//   If there is no matching single quote
//   then the sequence will continue until the end of the string.
// - . matches any single character unmatched by previous parts of the RegExps
const er=/[yYQqMLwIdDecihHKkms]o|(\w)\1*|''|'(''|[^'])+('|$)|./g;// This RegExp catches symbols escaped by quotes, and also
// sequences of symbols P, p, and the combinations like `PPPPPPPppppp`
const eo=/P+p+|P+|p+|''|'(''|[^'])+('|$)|./g;const ea=/^'([^]*?)'?$/;const ei=/''/g;const es=/[a-zA-Z]/;/**
 * The {@link format} function options.
 *//**
 * @name format
 * @alias formatDate
 * @category Common Helpers
 * @summary Format the date.
 *
 * @description
 * Return the formatted date string in the given format. The result may vary by locale.
 *
 * > ⚠️ Please note that the `format` tokens differ from Moment.js and other libraries.
 * > See: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 *
 * The characters wrapped between two single quotes characters (') are escaped.
 * Two single quotes in a row, whether inside or outside a quoted sequence, represent a 'real' single quote.
 * (see the last example)
 *
 * Format of the string is based on Unicode Technical Standard #35:
 * https://www.unicode.org/reports/tr35/tr35-dates.html#Date_Field_Symbol_Table
 * with a few additions (see note 7 below the table).
 *
 * Accepted patterns:
 * | Unit                            | Pattern | Result examples                   | Notes |
 * |---------------------------------|---------|-----------------------------------|-------|
 * | Era                             | G..GGG  | AD, BC                            |       |
 * |                                 | GGGG    | Anno Domini, Before Christ        | 2     |
 * |                                 | GGGGG   | A, B                              |       |
 * | Calendar year                   | y       | 44, 1, 1900, 2017                 | 5     |
 * |                                 | yo      | 44th, 1st, 0th, 17th              | 5,7   |
 * |                                 | yy      | 44, 01, 00, 17                    | 5     |
 * |                                 | yyy     | 044, 001, 1900, 2017              | 5     |
 * |                                 | yyyy    | 0044, 0001, 1900, 2017            | 5     |
 * |                                 | yyyyy   | ...                               | 3,5   |
 * | Local week-numbering year       | Y       | 44, 1, 1900, 2017                 | 5     |
 * |                                 | Yo      | 44th, 1st, 1900th, 2017th         | 5,7   |
 * |                                 | YY      | 44, 01, 00, 17                    | 5,8   |
 * |                                 | YYY     | 044, 001, 1900, 2017              | 5     |
 * |                                 | YYYY    | 0044, 0001, 1900, 2017            | 5,8   |
 * |                                 | YYYYY   | ...                               | 3,5   |
 * | ISO week-numbering year         | R       | -43, 0, 1, 1900, 2017             | 5,7   |
 * |                                 | RR      | -43, 00, 01, 1900, 2017           | 5,7   |
 * |                                 | RRR     | -043, 000, 001, 1900, 2017        | 5,7   |
 * |                                 | RRRR    | -0043, 0000, 0001, 1900, 2017     | 5,7   |
 * |                                 | RRRRR   | ...                               | 3,5,7 |
 * | Extended year                   | u       | -43, 0, 1, 1900, 2017             | 5     |
 * |                                 | uu      | -43, 01, 1900, 2017               | 5     |
 * |                                 | uuu     | -043, 001, 1900, 2017             | 5     |
 * |                                 | uuuu    | -0043, 0001, 1900, 2017           | 5     |
 * |                                 | uuuuu   | ...                               | 3,5   |
 * | Quarter (formatting)            | Q       | 1, 2, 3, 4                        |       |
 * |                                 | Qo      | 1st, 2nd, 3rd, 4th                | 7     |
 * |                                 | QQ      | 01, 02, 03, 04                    |       |
 * |                                 | QQQ     | Q1, Q2, Q3, Q4                    |       |
 * |                                 | QQQQ    | 1st quarter, 2nd quarter, ...     | 2     |
 * |                                 | QQQQQ   | 1, 2, 3, 4                        | 4     |
 * | Quarter (stand-alone)           | q       | 1, 2, 3, 4                        |       |
 * |                                 | qo      | 1st, 2nd, 3rd, 4th                | 7     |
 * |                                 | qq      | 01, 02, 03, 04                    |       |
 * |                                 | qqq     | Q1, Q2, Q3, Q4                    |       |
 * |                                 | qqqq    | 1st quarter, 2nd quarter, ...     | 2     |
 * |                                 | qqqqq   | 1, 2, 3, 4                        | 4     |
 * | Month (formatting)              | M       | 1, 2, ..., 12                     |       |
 * |                                 | Mo      | 1st, 2nd, ..., 12th               | 7     |
 * |                                 | MM      | 01, 02, ..., 12                   |       |
 * |                                 | MMM     | Jan, Feb, ..., Dec                |       |
 * |                                 | MMMM    | January, February, ..., December  | 2     |
 * |                                 | MMMMM   | J, F, ..., D                      |       |
 * | Month (stand-alone)             | L       | 1, 2, ..., 12                     |       |
 * |                                 | Lo      | 1st, 2nd, ..., 12th               | 7     |
 * |                                 | LL      | 01, 02, ..., 12                   |       |
 * |                                 | LLL     | Jan, Feb, ..., Dec                |       |
 * |                                 | LLLL    | January, February, ..., December  | 2     |
 * |                                 | LLLLL   | J, F, ..., D                      |       |
 * | Local week of year              | w       | 1, 2, ..., 53                     |       |
 * |                                 | wo      | 1st, 2nd, ..., 53th               | 7     |
 * |                                 | ww      | 01, 02, ..., 53                   |       |
 * | ISO week of year                | I       | 1, 2, ..., 53                     | 7     |
 * |                                 | Io      | 1st, 2nd, ..., 53th               | 7     |
 * |                                 | II      | 01, 02, ..., 53                   | 7     |
 * | Day of month                    | d       | 1, 2, ..., 31                     |       |
 * |                                 | do      | 1st, 2nd, ..., 31st               | 7     |
 * |                                 | dd      | 01, 02, ..., 31                   |       |
 * | Day of year                     | D       | 1, 2, ..., 365, 366               | 9     |
 * |                                 | Do      | 1st, 2nd, ..., 365th, 366th       | 7     |
 * |                                 | DD      | 01, 02, ..., 365, 366             | 9     |
 * |                                 | DDD     | 001, 002, ..., 365, 366           |       |
 * |                                 | DDDD    | ...                               | 3     |
 * | Day of week (formatting)        | E..EEE  | Mon, Tue, Wed, ..., Sun           |       |
 * |                                 | EEEE    | Monday, Tuesday, ..., Sunday      | 2     |
 * |                                 | EEEEE   | M, T, W, T, F, S, S               |       |
 * |                                 | EEEEEE  | Mo, Tu, We, Th, Fr, Sa, Su        |       |
 * | ISO day of week (formatting)    | i       | 1, 2, 3, ..., 7                   | 7     |
 * |                                 | io      | 1st, 2nd, ..., 7th                | 7     |
 * |                                 | ii      | 01, 02, ..., 07                   | 7     |
 * |                                 | iii     | Mon, Tue, Wed, ..., Sun           | 7     |
 * |                                 | iiii    | Monday, Tuesday, ..., Sunday      | 2,7   |
 * |                                 | iiiii   | M, T, W, T, F, S, S               | 7     |
 * |                                 | iiiiii  | Mo, Tu, We, Th, Fr, Sa, Su        | 7     |
 * | Local day of week (formatting)  | e       | 2, 3, 4, ..., 1                   |       |
 * |                                 | eo      | 2nd, 3rd, ..., 1st                | 7     |
 * |                                 | ee      | 02, 03, ..., 01                   |       |
 * |                                 | eee     | Mon, Tue, Wed, ..., Sun           |       |
 * |                                 | eeee    | Monday, Tuesday, ..., Sunday      | 2     |
 * |                                 | eeeee   | M, T, W, T, F, S, S               |       |
 * |                                 | eeeeee  | Mo, Tu, We, Th, Fr, Sa, Su        |       |
 * | Local day of week (stand-alone) | c       | 2, 3, 4, ..., 1                   |       |
 * |                                 | co      | 2nd, 3rd, ..., 1st                | 7     |
 * |                                 | cc      | 02, 03, ..., 01                   |       |
 * |                                 | ccc     | Mon, Tue, Wed, ..., Sun           |       |
 * |                                 | cccc    | Monday, Tuesday, ..., Sunday      | 2     |
 * |                                 | ccccc   | M, T, W, T, F, S, S               |       |
 * |                                 | cccccc  | Mo, Tu, We, Th, Fr, Sa, Su        |       |
 * | AM, PM                          | a..aa   | AM, PM                            |       |
 * |                                 | aaa     | am, pm                            |       |
 * |                                 | aaaa    | a.m., p.m.                        | 2     |
 * |                                 | aaaaa   | a, p                              |       |
 * | AM, PM, noon, midnight          | b..bb   | AM, PM, noon, midnight            |       |
 * |                                 | bbb     | am, pm, noon, midnight            |       |
 * |                                 | bbbb    | a.m., p.m., noon, midnight        | 2     |
 * |                                 | bbbbb   | a, p, n, mi                       |       |
 * | Flexible day period             | B..BBB  | at night, in the morning, ...     |       |
 * |                                 | BBBB    | at night, in the morning, ...     | 2     |
 * |                                 | BBBBB   | at night, in the morning, ...     |       |
 * | Hour [1-12]                     | h       | 1, 2, ..., 11, 12                 |       |
 * |                                 | ho      | 1st, 2nd, ..., 11th, 12th         | 7     |
 * |                                 | hh      | 01, 02, ..., 11, 12               |       |
 * | Hour [0-23]                     | H       | 0, 1, 2, ..., 23                  |       |
 * |                                 | Ho      | 0th, 1st, 2nd, ..., 23rd          | 7     |
 * |                                 | HH      | 00, 01, 02, ..., 23               |       |
 * | Hour [0-11]                     | K       | 1, 2, ..., 11, 0                  |       |
 * |                                 | Ko      | 1st, 2nd, ..., 11th, 0th          | 7     |
 * |                                 | KK      | 01, 02, ..., 11, 00               |       |
 * | Hour [1-24]                     | k       | 24, 1, 2, ..., 23                 |       |
 * |                                 | ko      | 24th, 1st, 2nd, ..., 23rd         | 7     |
 * |                                 | kk      | 24, 01, 02, ..., 23               |       |
 * | Minute                          | m       | 0, 1, ..., 59                     |       |
 * |                                 | mo      | 0th, 1st, ..., 59th               | 7     |
 * |                                 | mm      | 00, 01, ..., 59                   |       |
 * | Second                          | s       | 0, 1, ..., 59                     |       |
 * |                                 | so      | 0th, 1st, ..., 59th               | 7     |
 * |                                 | ss      | 00, 01, ..., 59                   |       |
 * | Fraction of second              | S       | 0, 1, ..., 9                      |       |
 * |                                 | SS      | 00, 01, ..., 99                   |       |
 * |                                 | SSS     | 000, 001, ..., 999                |       |
 * |                                 | SSSS    | ...                               | 3     |
 * | Timezone (ISO-8601 w/ Z)        | X       | -08, +0530, Z                     |       |
 * |                                 | XX      | -0800, +0530, Z                   |       |
 * |                                 | XXX     | -08:00, +05:30, Z                 |       |
 * |                                 | XXXX    | -0800, +0530, Z, +123456          | 2     |
 * |                                 | XXXXX   | -08:00, +05:30, Z, +12:34:56      |       |
 * | Timezone (ISO-8601 w/o Z)       | x       | -08, +0530, +00                   |       |
 * |                                 | xx      | -0800, +0530, +0000               |       |
 * |                                 | xxx     | -08:00, +05:30, +00:00            | 2     |
 * |                                 | xxxx    | -0800, +0530, +0000, +123456      |       |
 * |                                 | xxxxx   | -08:00, +05:30, +00:00, +12:34:56 |       |
 * | Timezone (GMT)                  | O...OOO | GMT-8, GMT+5:30, GMT+0            |       |
 * |                                 | OOOO    | GMT-08:00, GMT+05:30, GMT+00:00   | 2     |
 * | Timezone (specific non-locat.)  | z...zzz | GMT-8, GMT+5:30, GMT+0            | 6     |
 * |                                 | zzzz    | GMT-08:00, GMT+05:30, GMT+00:00   | 2,6   |
 * | Seconds timestamp               | t       | 512969520                         | 7     |
 * |                                 | tt      | ...                               | 3,7   |
 * | Milliseconds timestamp          | T       | 512969520900                      | 7     |
 * |                                 | TT      | ...                               | 3,7   |
 * | Long localized date             | P       | 04/29/1453                        | 7     |
 * |                                 | PP      | Apr 29, 1453                      | 7     |
 * |                                 | PPP     | April 29th, 1453                  | 7     |
 * |                                 | PPPP    | Friday, April 29th, 1453          | 2,7   |
 * | Long localized time             | p       | 12:00 AM                          | 7     |
 * |                                 | pp      | 12:00:00 AM                       | 7     |
 * |                                 | ppp     | 12:00:00 AM GMT+2                 | 7     |
 * |                                 | pppp    | 12:00:00 AM GMT+02:00             | 2,7   |
 * | Combination of date and time    | Pp      | 04/29/1453, 12:00 AM              | 7     |
 * |                                 | PPpp    | Apr 29, 1453, 12:00:00 AM         | 7     |
 * |                                 | PPPppp  | April 29th, 1453 at ...           | 7     |
 * |                                 | PPPPpppp| Friday, April 29th, 1453 at ...   | 2,7   |
 * Notes:
 * 1. "Formatting" units (e.g. formatting quarter) in the default en-US locale
 *    are the same as "stand-alone" units, but are different in some languages.
 *    "Formatting" units are declined according to the rules of the language
 *    in the context of a date. "Stand-alone" units are always nominative singular:
 *
 *    `format(new Date(2017, 10, 6), 'do LLLL', {locale: cs}) //=> '6. listopad'`
 *
 *    `format(new Date(2017, 10, 6), 'do MMMM', {locale: cs}) //=> '6. listopadu'`
 *
 * 2. Any sequence of the identical letters is a pattern, unless it is escaped by
 *    the single quote characters (see below).
 *    If the sequence is longer than listed in table (e.g. `EEEEEEEEEEE`)
 *    the output will be the same as default pattern for this unit, usually
 *    the longest one (in case of ISO weekdays, `EEEE`). Default patterns for units
 *    are marked with "2" in the last column of the table.
 *
 *    `format(new Date(2017, 10, 6), 'MMM') //=> 'Nov'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMM') //=> 'November'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMMM') //=> 'N'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMMMM') //=> 'November'`
 *
 *    `format(new Date(2017, 10, 6), 'MMMMMMM') //=> 'November'`
 *
 * 3. Some patterns could be unlimited length (such as `yyyyyyyy`).
 *    The output will be padded with zeros to match the length of the pattern.
 *
 *    `format(new Date(2017, 10, 6), 'yyyyyyyy') //=> '00002017'`
 *
 * 4. `QQQQQ` and `qqqqq` could be not strictly numerical in some locales.
 *    These tokens represent the shortest form of the quarter.
 *
 * 5. The main difference between `y` and `u` patterns are B.C. years:
 *
 *    | Year | `y` | `u` |
 *    |------|-----|-----|
 *    | AC 1 |   1 |   1 |
 *    | BC 1 |   1 |   0 |
 *    | BC 2 |   2 |  -1 |
 *
 *    Also `yy` always returns the last two digits of a year,
 *    while `uu` pads single digit years to 2 characters and returns other years unchanged:
 *
 *    | Year | `yy` | `uu` |
 *    |------|------|------|
 *    | 1    |   01 |   01 |
 *    | 14   |   14 |   14 |
 *    | 376  |   76 |  376 |
 *    | 1453 |   53 | 1453 |
 *
 *    The same difference is true for local and ISO week-numbering years (`Y` and `R`),
 *    except local week-numbering years are dependent on `options.weekStartsOn`
 *    and `options.firstWeekContainsDate` (compare [getISOWeekYear](https://date-fns.org/docs/getISOWeekYear)
 *    and [getWeekYear](https://date-fns.org/docs/getWeekYear)).
 *
 * 6. Specific non-location timezones are currently unavailable in `date-fns`,
 *    so right now these tokens fall back to GMT timezones.
 *
 * 7. These patterns are not in the Unicode Technical Standard #35:
 *    - `i`: ISO day of week
 *    - `I`: ISO week of year
 *    - `R`: ISO week-numbering year
 *    - `t`: seconds timestamp
 *    - `T`: milliseconds timestamp
 *    - `o`: ordinal number modifier
 *    - `P`: long localized date
 *    - `p`: long localized time
 *
 * 8. `YY` and `YYYY` tokens represent week-numbering years but they are often confused with years.
 *    You should enable `options.useAdditionalWeekYearTokens` to use them. See: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 *
 * 9. `D` and `DD` tokens represent days of the year but they are often confused with days of the month.
 *    You should enable `options.useAdditionalDayOfYearTokens` to use them. See: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 *
 * @param date - The original date
 * @param format - The string of tokens
 * @param options - An object with options
 *
 * @returns The formatted date string
 *
 * @throws `date` must not be Invalid Date
 * @throws `options.locale` must contain `localize` property
 * @throws `options.locale` must contain `formatLong` property
 * @throws use `yyyy` instead of `YYYY` for formatting years using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws use `yy` instead of `YY` for formatting years using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws use `d` instead of `D` for formatting days of the month using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws use `dd` instead of `DD` for formatting days of the month using [format provided] to the input [input provided]; see: https://github.com/date-fns/date-fns/blob/master/docs/unicodeTokens.md
 * @throws format string contains an unescaped latin alphabet character
 *
 * @example
 * // Represent 11 February 2014 in middle-endian format:
 * const result = format(new Date(2014, 1, 11), 'MM/dd/yyyy')
 * //=> '02/11/2014'
 *
 * @example
 * // Represent 2 July 2014 in Esperanto:
 * import { eoLocale } from 'date-fns/locale/eo'
 * const result = format(new Date(2014, 6, 2), "do 'de' MMMM yyyy", {
 *   locale: eoLocale
 * })
 * //=> '2-a de julio 2014'
 *
 * @example
 * // Escape string by single quote characters:
 * const result = format(new Date(2014, 6, 2, 15), "h 'o''clock'")
 * //=> "3 o'clock"
 */function ec(t,e,n){const r=tv();const o=n?.locale??r.locale??tm;const a=n?.firstWeekContainsDate??n?.locale?.options?.firstWeekContainsDate??r.firstWeekContainsDate??r.locale?.options?.firstWeekContainsDate??1;const i=n?.weekStartsOn??n?.locale?.options?.weekStartsOn??r.weekStartsOn??r.locale?.options?.weekStartsOn??0;const s=W(t,n?.in);if(!ee(s)){throw new RangeError("Invalid time value")}let c=e.match(eo).map(t=>{const e=t[0];if(e==="p"||e==="P"){const n=t1[e];return n(t,o.formatLong)}return t}).join("").match(er).map(t=>{// Replace two single quote characters with one single quote character
if(t==="''"){return{isToken:false,value:"'"}}const e=t[0];if(e==="'"){return{isToken:false,value:eu(t)}}if(tJ[e]){return{isToken:true,value:t}}if(e.match(es)){throw new RangeError("Format string contains an unescaped latin alphabet character `"+e+"`")}return{isToken:false,value:t}});// invoke localize preprocessor (only for french locales at the moment)
if(o.localize.preprocessor){c=o.localize.preprocessor(s,c)}const u={firstWeekContainsDate:a,weekStartsOn:i,locale:o};return c.map(r=>{if(!r.isToken)return r.value;const a=r.value;if(!n?.useAdditionalWeekYearTokens&&t5(a)||!n?.useAdditionalDayOfYearTokens&&t4(a)){t8(a,e,String(t))}const i=tJ[a[0]];return i(s,a,o.localize,u)}).join("")}function eu(t){const e=t.match(ea);if(!e){return t}return e[1].replace(ei,"'")}// Fallback for modularized imports:
/* export default */const ed=/* unused pure expression or super */null&&ec;// CONCATENATED MODULE: ./assets/src/js/utils.js
/**
 * Converts a local date to GMT (Greenwich Mean Time).
 * 
 * @param {Date} date - The local date to convert to GMT
 * @param {string} [dateFormat='yyyy-MM-dd HH:mm:ss'] - The format string for the output date
 * @returns {string} The formatted GMT date string
 * 
 * @since v3.8.0
 */var el=function(t){var e=arguments.length>1&&arguments[1]!==void 0?arguments[1]:"yyyy-MM-dd HH:mm:ss";var n=t.getTimezoneOffset();var r=E(t,n);return ec(r,e)};/**
 * Converts a GMT date to local date based on the user's timezone.
 * 
 * @param {string|Date} date - The GMT date to convert to local time
 * @returns {Date} The converted local date object
 * 
 * @since v3.8.0
 */var ef=t=>{var e=new Date(t);var n=e.getTimezoneOffset();return addMinutes(e,-n)};/**
 * Validates if a string is a valid email address format.
 * 
 * @param {string} email - The email string to validate
 * @returns {boolean} True if the email format is valid, false otherwise
 * 
 * @since v3.8.1
 */var eh=t=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t);/**
 * Creates a FormData object from a data object and automatically adds security nonce.
 * 
 * @param {Object} [data={}] - The data object to convert to FormData
 * @returns {FormData} A FormData object with the provided data and security nonce
 * 
 * @example
 * const formData = tutorFormData({
 *   action: 'save_lesson_note',
 *   lesson_id: 123,
 *   note_text: 'My note content'
 * });
 * 
 * @since v3.9.0
 */function em(){var t=arguments.length>0&&arguments[0]!==void 0?arguments[0]:{};var e=new FormData;Object.keys(t).forEach(n=>e.set(n,t[n]));e.set(window.tutor_get_nonce_data(true).key,window.tutor_get_nonce_data(true).value);return e}/**
 * Handles AJAX requests to the WordPress admin-ajax.php endpoint.
 * 
 * @param {FormData} formData - The FormData object containing the request data
 * @returns {Promise<Response|undefined>} The fetch Response object, or undefined if an error occurs
 * 
 * @example
 * const formData = tutorFormData({ action: 'get_lesson_data', lesson_id: 123 });
 * const response = await ajaxHandler(formData);
 * const data = await response.json();
 * 
 * @since v3.9.0
 */function eg(t){return e(function*(){try{var e=yield fetch(window._tutorobject.ajaxurl,{method:"POST",body:t});return e}catch(t){tutor_toast(__("Operation failed","tutor-pro"),t,"error")}})()}var ew=(t,e)=>{var{__:n}=wp.i18n;var{data:r={}}=t||{};var{message:o=e||n("Something Went Wrong!","tutor-pro")}=r;return o};// CONCATENATED MODULE: ./assets/src/js/device-management.js
if(!window.tutor_toast){window.tutor_toast=function(t,e,n){var r=arguments.length>3&&arguments[3]!==void 0?arguments[3]:true;if(!jQuery(".tutor-toast-parent").length){jQuery("body").append('<div class="tutor-toast-parent tutor-toast-right"></div>')}var o=n=="success"?"success":n=="error"?"danger":n=="warning"?"warning":"primary";var a=n=="success"?"tutor-icon-mark":n=="error"?"tutor-icon-times":"tutor-icon-circle-info-o";var i=e!==undefined&&e!==null&&e.trim()!=="";var s=jQuery('\n            <div class="tutor-notification tutor-is-'.concat(o,' tutor-mb-16">\n                <div class="tutor-notification-icon">\n                    <i class="').concat(a,'"></i>\n                </div>\n                <div class="tutor-notification-content">\n                <h5>').concat(t,'</h5>\n                <p class="').concat(!i?"tutor-d-none":"",'">').concat(e,'</p>\n                </div>\n                <button class="tutor-notification-close">\n                    <i class="fas fa-times"></i>\n                </button>\n            </div>\n        '));s.find(".tutor-noti-close").click(function(){s.remove()});jQuery(".tutor-toast-parent").append(s);if(r){setTimeout(function(){if(s){s.fadeOut("fast",function(){jQuery(this).remove()})}},5e3)}}}document.addEventListener("DOMContentLoaded",function(){var{__}=wp.i18n;/**
     * Clear all active login sessions
     * From: Tutor login, 
     *       Tutor modal login
     *       WP Native login
     *       WooCommerce my-profile login
     */var t=document.querySelector(".tutor-login-form-wrapper, .tutor-login-modal, #login_error, .woocommerce-error");if(t){t.onclick=n=>e(function*(){var e=n.target;var r=e;// If clear session btn clicked
    if(e.hasAttribute("id")&&e.getAttribute("id")==="tutor-remove-active-logins"){n.preventDefault();var o=document.getElementById("tutor-remove-logins-wrapper");var a=o.closest(".tutor-alert");if(!a){a=document.getElementById("login_error")}// For woocommerce my account login page.
    if(!a){a=document.querySelector("ul.woocommerce-error")}// Prepare form data.
    var i=em({action:"tutor_remove_all_active_logins"});try{// Show waiting indicator.
    o.innerHTML='<span class="tutor-color-subdued">'.concat(__("Please wait...","tutor-pro"),"</span>");var s=yield eg(i);var c=yield s.json();var{success:u,data:d}=c;if(u){// Show success message.            
    if(a.classList.contains("tutor-warning")){a.classList.remove("tutor-warning");a.classList.add("tutor-success")}if(a.hasAttribute("id")){a.style.borderLeftColor="#6eea98"}// For woocommerce my account login page.
    if(a.classList.contains("woocommerce-error")){a.classList.add("woocommerce-message")}a.innerHTML='<div class="tutor-alert-content tutor-d-flex tutor-align-start tutor-gap-1">\n                            <div class="tutor-alert-icon">\n                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" role="presentation" aria-hidden="true"><path d="M19.678 15.534c.226.36.337.774.32 1.194-.01.394-.119.78-.32 1.125-.204.34-.49.627-.835.837-.374.211-.802.318-1.235.31H6.41a2.419 2.419 0 0 1-1.232-.31 2.539 2.539 0 0 1-.858-.837 2.265 2.265 0 0 1 0-2.319L9.94 6.162c.203-.368.512-.672.89-.875a2.523 2.523 0 0 1 2.341 0c.385.2.701.504.91.875l5.598 9.372Zm-6.866-4.179c0-.213.023-.426.039-.643.015-.216.028-.436.041-.655 0-.077-.005-.153-.016-.229a.93.93 0 0 0-.064-.21.89.89 0 0 0-.429-.423.906.906 0 0 0-1.117.287.981.981 0 0 0-.188.59c0 .14.022.279.032.415.01.136.02.275.032.414.029.522.06 1.042.093 1.545.032.504.06 1.033.093 1.546a.615.615 0 0 0 .203.443.66.66 0 0 0 .468.175.66.66 0 0 0 .47-.18.657.657 0 0 0 .202-.454v-.618c.023-.33.042-.667.064-1.004.023-.337.058-.668.077-.999ZM12 16.922a.896.896 0 0 0 .618-.247.712.712 0 0 0 .26-.581.828.828 0 0 0-.248-.608.885.885 0 0 0-.62-.26.91.91 0 0 0-.63.238.854.854 0 0 0-.27.599c0 .228.095.446.261.608a.905.905 0 0 0 .63.251Z" fill="currentColor"></path></svg>\n                            </div>\n                            <div class="tutor-alert-text">'.concat(__("All of your active login sessions have been removed. You can login now.","tutor-pro"),"</div>\n                        </div>")}else{// Check if limit login error
    if(Array.isArray(d)){var l=d[0];if(l&&l.code==="tutor_login_limit"){t.insertAdjacentHTML("afterbegin",l.message);return}}tutor_toast(__("Failed","tutor-pro"),d,"error");o.innerHTML=r}}catch(t){console.log(t);tutor_toast(__("Something went wrong","tutor-pro"),__("Please try again after reloading page!","tutor-pro"),"error")}}})()}});// EXTERNAL MODULE: ./assets/src/js/download-invoice.js
var ev=n(34);// CONCATENATED MODULE: ./assets/src/js/front.js
/**
 * Frontend scripts
 *
 * @since v2.0.9
 *//* Sidebar sticky */var ey=false;window.addEventListener("DOMContentLoaded",()=>{var{__}=wp.i18n;var t=document.querySelector(".tutor-course-details-page");if(t){var n=document.querySelector(".tutor-single-course-sidebar");var r=n.hasAttribute("data-tutor-sticky-sidebar");if(r){var o=document.getElementsByTagName("header")[0];var a="".concat(o.offsetHeight,"px");var i=o.classList.value.includes("sticky");function s(){var t=window.scrollY;var e=200;var r=t>=e;var o=window.innerWidth>=1200;if(o){if(r){if(!ey){n.classList.add("tutor-sidebar-sticky");var s="max-height: 80vh; overflow-y: scroll; top: ".concat(i?a:"");n.setAttribute("style",s);n.scrollTop=0;ey=true}}else{n.classList.remove("tutor-sidebar-sticky");n.removeAttribute("style");ey=false}}}window.addEventListener("scroll",s)}}/**
   * Handle gift this course form submission.
   *
   * @since 3.8.0
   */var c=document.querySelector("#tutor-gift-this-course-form");var u=document.querySelectorAll(".tutor-gift-card-button");if(c){c.addEventListener("submit",function(t){return e(function*(){t.preventDefault();var e=__("Something went wrong, please try again","tutor-pro");var n=new FormData(t.target);n.set("action","tutor_pro_gift_proceed_to_checkout");n.set("datetime",el(new Date("".concat(n.get("gift_date")," ").concat(n.get("gift_time")))));n.set("notify_me",n.get("notify_me")==="on"?1:0);var r=c.querySelector("button[type=submit]");try{r.setAttribute("disabled",true);r.classList.add("is-loading");var o=yield eg(n);var{status_code:a,message:i,data:s}=yield o.json();if(a===200){if(s===null||s===void 0?void 0:s.url){window.location.href=s.url}else{tutor_toast(__("Failed","tutor-pro"),i||e,"error")}}else{tutor_toast(__("Failed","tutor-pro"),i||e,"error")}}catch(t){tutor_toast(__("Failed","tutor-pro"),e,"error")}finally{r.removeAttribute("disabled");r.classList.remove("is-loading")}})()})}u.forEach(t=>{t.addEventListener("click",t=>e(function*(){var e=t.target;var n=e.dataset.courseId;var r=e.dataset.referenceId;if(!n||!r){tutor_toast(__("Failed","tutor-pro"),__("Invalid gift, please try again","tutor-pro"),"error");return}try{e.classList.add("tutor-btn-loading");e.setAttribute("disabled",true);var o=new FormData;o.set("action","tutor_pro_gift_enrollment");o.set("course_id",n);o.set("reference_id",r);o.set(_tutorobject.nonce_key,_tutorobject._tutor_nonce);var a=yield eg(o);var i=yield a.json();var{status_code:s,message:c,data:u}=i;if(s===200){TutorCore.modal.showModal("tutor-greetings-popup-".concat(n));e.closest(".tutor-gift-course-card").remove()}else{tutor_toast(__("Failed","tutor-pro"),c||defaultErrorMessage,"error")}}catch(t){tutor_toast(__("Error","tutor-pro"),t,"error")}finally{e.classList.remove("tutor-btn-loading");e.removeAttribute("disabled")}})())});/**
   * Fix: YouTube poster issue
   *
   * @since 4.0.0
   */document.addEventListener("tutorPlayerReady",t=>{var{plyr:e}=t.detail;var n=t.target;if((e===null||e===void 0?void 0:e.provider)==="youtube"){var r;var o=n===null||n===void 0?void 0:(r=n.closest(".plyr--youtube.plyr__poster-enabled"))===null||r===void 0?void 0:r.querySelector(".plyr__poster");if(o){e.on("play",()=>o.style.opacity="0");e.on("ended",()=>o.style.opacity="1")}}})})})()})();