/** @module */

/**
 * @file
 *
 * Summary.
 * <p>CDC router.</p>
 *
 * {@link https://vercel.com/guides/using-express-with-vercel Vercel}
 * seems to require a /api folder with an index.js file in it to work.
 * Static files, such as .css and .html, lie in /public folder.
 *
 * <p>Therefore, it loads files directly from /public or /api, e.g.,</p>
 * <ul>
 *   <li>{@link https://cdc-express.vercel.app/}, </li>
 *   <li>{@link https://cdc-express.vercel.app/api/cgi?np=10&tax=0&pv=450&pp=500}, </li>
 *   <li>{@link https://cdc-express.vercel.app/favicon.ico} or </li>
 *   <li>{@link https://cdc-express.vercel.app/cd.css}.</li>
 * </ul>
 *
 * <p>However, running locally, it would be</p>
 * <ul>
 *   <li>{@link http://localhost:3000/api/cdc},</li>
 *   <li>{@link http://localhost:3000/api/cgi?np=10&tax=0&pv=450&pp=500},</li>
 *   <li>{@link http://localhost:3000/api/favicon.ico} or</li>
 *   <li>{@link http://localhost:3000/api/cd.css}.</li>
 * </ul>
 *
 * @requires module:rational
 * @requires express
 *
 * @author Paulo Roma
 * @since 01/11/2023
 * @see <a href="../routes/routes.js">source</a>
 * @see <a href="https://cdc-express.vercel.app">link</a>
 * @see https://expressjs.com/en/guide/routing.html#express-router
 */

"use strict";

const rational = require("../public/rational.cjs");
const express = require("express");
/**
 * @var {router} router Express router.
 * @see https://expressjs.com/en/4x/api.html#router
 */
const router = express.Router();

// for POST
router.use(express.json());
router.use(express.urlencoded({ extended: true }));

/**
 * Middleware functions are functions that have access to the request object (req),
 * the response object (res), and the next function in the application’s request-response cycle.
 *
 * <p>The next function is a function in the Express router which, when invoked,
 * executes the middleware succeeding the current middleware.</p>
 *
 * @callback middleware
 * @param {Object} req HTTP request argument to the middleware function, called "req" by convention.
 * @param {Object} res HTTP response argument to the middleware function, called "res" by convention.
 * @param {Object} next Callback argument to the middleware function, called "next" by convention.
 * @see https://expressjs.com/en/guide/writing-middleware.html
 */

/**
 * Returns an HTML page with the CDC calculation results.
 *
 * @param {Array<Number>} arr
 *  [parcelas, taxa, preço à vista, preço a prazo, valor a voltar, meses a voltar].
 * @param {Boolean} prt whether print output.
 * @returns {String} HTML code.
 */
function createHTML(arr, prt = false) {
  let [np, t, pv, pp, pb, nb] = arr;
  t *= 0.01;

  let pmt = pp / np;
  let cf = 0;
  let i = 0;
  let ti = 0;
  let message = "";
  let dp = rational.getDownPayment();

  try {
    if (t === 0) {
      if (pmt >= pv) {
        throw new Error(
          `Prestação (\$${pmt.toFixed(2)}) é maior do que o empréstimo`
        );
      }
      [ti, i] = rational.getInterest(pp, pv, np);
      t = ti * 0.01;
    }
    cf = rational.CF(t, np);
    pmt = cf * pv;
    // there is no sense in a montly payment greater than the loan...
    if (pmt >= pv) {
      throw new Error("Prestação é maior do que o empréstimo");
    }
  } catch (e) {
    message += e.message;
  } finally {
    if (dp) {
      np -= 1; // uma prestação a menos
      pmt /= 1 + t; // diminui a prestação
      pv -= pmt; // preço à vista menos a entrada
      cf = pmt / pv; // recalculate cf
    }
  }

  let ptb = rational.priceTable(np, pv, t, pmt);
  let hpt = rational.htmlPriceTable(ptb);

  if (pb == 0 && nb > 0) {
    pb = pmt * nb;
  }

  return `<html>
  <head>
      <title>CDC - Crédito Direto ao Consumidor (nodejs)</title>
      <link rel="stylesheet" href="/cd.css">
  </head>
  <body style="background-image: url('/IMAGEM/stone/yell_roc.jpg')">
    <div id="greenBox" class="rectangle">
      <h4>Parcelamento: ${dp ? "1+" : ""}${np} meses</h4>
      <h4>Taxa: ${(100 * t).toFixed(2)}% ao mês = ${(
    ((1 + t) ** 12 - 1) *
    100.0
  ).toFixed(2)}% ao ano</h4>
      <h4>Valor Financiado: \$${pv.toFixed(2)}</h4>
      <h4>Valor Final: \$${pp.toFixed(2)}</h4>
      <h4>Valor a Voltar: \$${pb.toFixed(2)}</h4>
      <h4>Meses a Voltar: ${nb}</h4>
      <h4>Entrada: ${dp}</h4>
    </div>

    <div id="blueBox" class="rectangle">
      <h2><mark>${message}</mark></h2>
      <h4>Coeficiente de Financiamento: ${cf.toFixed(6)}</h4>
      <h4>Prestação: ${cf.toFixed(6)} * \$${pv.toFixed(2)}= \$${pmt.toFixed(
    2
  )} ao mês</h4>
      <h4>Valor Pago com Juros: \$${ptb.slice(-1)[0][1].toFixed(2)}</h4>
      <h4>Taxa Real (${i} iterações): ${ti.toFixed(4)}% ao mês</h4>
      <h4>Valor Corrigido: \$${
        nb > 0 ? rational.presentValue(pb, nb, t, false)[1].toFixed(2) : 0
      }</h4>
    </div>

    <div id="redBox" class="rectangle">
      <h4>${hpt}</h4>
    </div>
    <script>
      ${prt ? "print()" : ""};
    </script>
  </body>
  </html>`;
}

/**
 * Route displaying CDC calculation results.
 * @name post/api
 * @function
 * @memberof module:routes/routes
 * @inner
 * @param {String} path - path for which the middleware function is invoked.
 * @param {middleware} callback - a middleware function.
 */
router.post("/", (req, res) => {
  let arr = [
    +req.body.np,
    +req.body.tax,
    +req.body.pv,
    +req.body.pp,
    +req.body.pb,
    +req.body.nb,
  ];
  let dp = typeof req.body.dp !== "undefined";
  let prt = typeof req.body.pdf !== "undefined";
  rational.setDownPayment(dp);
  res.send(createHTML(arr, prt));
});

/**
 * Route displaying CDC calculation results.
 * @name get/api
 * @function
 * @memberof module:routes/routes
 * @inner
 * @param {String} path - path for which the middleware function is invoked.
 * @param {middleware} callback - a middleware function.
 */
router.get("/", (req, res) => {
  let arr = [
    +req.query.np,
    +req.query.tax,
    +req.query.pv,
    +req.query.pp,
    +req.query.pb,
    +req.query.nb,
  ];
  let dp = typeof req.query.dp !== "undefined";
  let prt = typeof req.query.pdf !== "undefined";
  rational.setDownPayment(dp);
  res.send(createHTML(arr, prt));
});

/**
 * Route displaying CDC rational discount.
 * @name get/api/cgi
 * @function
 * @memberof module:routes/routes
 * @inner
 * @param {String} path - path for which the middleware function is invoked.
 * @param {middleware} callback - a middleware function.
 */
router.get("/cgi", (req, res) => {
  let arr = [+req.query.np, +req.query.tax, +req.query.pv, +req.query.pp];
  let [np, t, pv, pp] = arr;
  let dp = typeof req.query.dp !== "undefined";
  let prt = typeof req.query.pdf !== "undefined";
  rational.setDownPayment(dp);
  let result = rational.rational_discount(np, t * 0.01, pp, pv, true);
  res.send(`<html>
    <head>
        <title>CDC - Crédito Direto ao Consumidor (nodejs)</title>
        <link rel="stylesheet" href="/cd.css">
        <style>
            body {
                background-color: #f0f0f2;
                background-image: url("/IMAGEM/stone/yell_roc.jpg");
                margin: 0;
                padding: 1em;
            }
        </style>
    </head>
    <body>
      <div id="redBox" class="rectangle">
        <pre>
        <code>
          <p>${result}</p>
        </code>
        </pre>
      </div>
      <script>
        ${prt ? "print()" : ""};
      </script>
    </body>
    </html>`);
});

/**
 * Route serving CDC main form.
 * @name get/api/cdc
 * @function
 * @memberof module:routes/routes
 * @inner
 * @param {String} path - path for which the middleware function is invoked.
 * @param {middleware} callback - a middleware function.
 */
router.all("/cdc", (req, res) => {
  res.sendFile("cdc.html", { root: "public" });
});

/**
 * Route displaying favicon.ico.
 * @name get/api/favicon
 * @function
 * @memberof module:routes/routes
 * @inner
 * @param {String} path - path for which the middleware function is invoked.
 * @param {middleware} callback - a middleware function.
 */
router.get("/favicon.ico", (req, res) => {
  res.sendFile("favicon.ico", { root: "public" });
});

/**
 * Route for sending the style sheet.
 * @name get/api/cd.css
 * @function
 * @memberof module:routes/routes
 * @inner
 * @param {String} path - path for which the middleware function is invoked.
 * @param {middleware} callback - a middleware function.
 */
router.get("/cd.css", (req, res) => {
  res.sendFile("cd.css", { root: "public" });
});

module.exports = router;
