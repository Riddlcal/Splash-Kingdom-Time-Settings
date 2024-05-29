const timerElement = document.getElementById("timer");
let defaultOpeningTimes = {};
function updateTimerWithNewValues() {
    fetch("/wp-json/splash-kingdom/v1/opening-times")
        .then((e) => e.json())
        .then((e) => {
            if (e && "Success" === e.message) {
                let t = e.splash_kingdom_opening_times;
                (defaultOpeningTimes = {
                    park1: convertArrayToObject(t["Paradise Island"]),
                    park2: convertArrayToObject(t["Air Patrol"]),
                    park3: convertArrayToObject(t["Wild West"]),
                    park4: convertArrayToObject(t["Timber Falls"]),
                }),
                    setTimerText();
            } else console.error("Failed to update timer.");
        })
        .catch((e) => {
            console.error("Error updating timer:", e);
        });
}
updateTimerWithNewValues();
const currentDay = new Date().getDay(),
    days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
    getNextOpeningDay = (e, t) => {
        let n = (t + 1) % 7,
            s = 0;
        for (; s < 7; ) {
            let r = defaultOpeningTimes[e][n];
            if (null !== r.s && null !== r.e) return n;
            (n = (n + 1) % 7), s++;
        }
        return t;
    },
    getNextOpeningInfo = (e, t) => {
        let n = (t + 1) % 7;
        for (; n !== t; ) {
            let s = defaultOpeningTimes[e][n];
            if (!isNaN(s.s.h) && !isNaN(s.s.m) && !isNaN(s.e.h) && !isNaN(s.e.m)) {
                let r;
                return { nextOpeningDayName: getDayName(n), nextOpeningTime: s.s };
            }
            n = (n + 1) % 7;
        }
        let i;
        return { nextOpeningDayName: getDayName(t), nextOpeningTime: defaultOpeningTimes[e][t].s };
    },
    getDayName = (e) => days[e];
function convertArrayToObject(e) {
    return e.reduce((e, t, n) => {
        if (null === t.start || null === t.end) e[n] = { s: null, e: null, disabled: 1 };
        else {
            let s = t.start.split(" "),
                r = t.end.split(" "),
                i = s[0].split(":"),
                a = r[0].split(":"),
                l = parseInt(i[0], 10),
                o = parseInt(i[1], 10),
                m = s[1],
                u = parseInt(a[0], 10),
                d = parseInt(a[1], 10),
                p = r[1];
            e[n] = {
                s: { h: m === "AM" ? l : (l === 12 ? 12 : l + 12), m: o },
                e: { h: p === "AM" ? u : (u === 12 ? 12 : u + 12), m: d },
                disabled: 0
            };
        }
        return e;
    }, {});
}
const formatTime = (e) => {
    let t = e.h % 12 || 12; // Adjusting for 12 PM
    return `${t}:${String(e.m).padStart(2, "0")} ${e.h >= 12 ? "PM" : "AM"}`;
};
    setTimerText = () => {
        let e = new Date(),
            t = e.getDay();
        e.getHours(), e.getMinutes();
        for (let n = 1; n <= 5; n++) {
            let s = `park${n}`,
                r = document.getElementById(s),
                i = document.getElementById(`${s}-timer`);
            if (!r || !i) continue;
            let { s: a, e: l, disabled: o } = defaultOpeningTimes[s][t];
            if (1 !== o && a && l && a.h && l.h) {
                let m = new Date(e);
                m.setHours(a.h, a.m, 0);
                let u = new Date(e);
                if ((u.setHours(l.h, l.m, 0), e >= m && e <= u)) i.innerHTML = ` are open from <span class="sk-timer-time">${formatTime(a)}</span> to <span class="sk-timer-time">${formatTime(l)}</span>. `;
                else {
                    let d = new Date(e);
                    if ((d.setDate(d.getDate() + 1), d.setHours(defaultOpeningTimes[s][(t + 1) % 7].s.h, defaultOpeningTimes[s][(t + 1) % 7].s.m, 0), e >= u && e < d)) {
                        let p = Math.floor((d - e) / 6e4),
                            g = Math.floor(p / 60),
                            f = p % 60;
                        g > 0 ? (i.innerHTML = ` open in <span class="sk-timer-time">${g} hour${1 !== g ? "s" : ""} and ${f} minute${1 !== f ? "s" : ""}</span>. `) : (i.innerHTML = ` open in <span class="sk-timer-time">${f} minute${1 !== f ? "s" : ""}</span>. `);
                    } else {
                        let T = Math.floor((m - e) / 6e4);
                        if (T < 0) {
                            let _ = new Date(e);
                            _.setDate(_.getDate() + 1), _.setHours(defaultOpeningTimes[s][(t + 1) % 7].s.h, defaultOpeningTimes[s][(t + 1) % 7].s.m, 0), (T = Math.floor((_ - e) / 6e4));
                        }
                        let y = Math.floor(T / 60),
                            c = T % 60;
                        y > 0 ? (i.innerHTML = ` open in <span class="sk-timer-time">${y} hour${1 !== y ? "s" : ""} and ${c} minute${1 !== c ? "s" : ""}</span>. `) : (i.innerHTML = ` open in <span class="sk-timer-time">${c} minute${1 !== c ? "s" : ""}</span>. `);
                    }
                }
            } else {
                let { nextOpeningDayName: h, nextOpeningTime: O } = getNextOpeningInfo(s, t);
                O ? (i.innerHTML = ` open ${h} at <span class="sk-timer-time">${formatTime(O)}</span> `) : (i.textContent = "We are closed. Please check the park's schedule for the next opening day.");
            }
        }
    };
setTimerText(), setInterval(setTimerText, 6e4);
