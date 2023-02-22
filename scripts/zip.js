const fs = require("node:fs");
const path = require("node:path");

const archiver = require("archiver");

const ignore = require("./ignored");
const pack = require("../package.json");
const name = "stancer";

const output = fs.createWriteStream(
  path.join(__dirname, "../", `${name}-${pack.version}.zip`)
);
const archive = archiver("zip", {
  zlib: {
    level: 9,
  },
});

output.on("close", () => {
  console.log(`Archive size: ${archive.pointer()} bytes`);
});

archive.on("warning", (err) => {
  throw err;
});

archive.on("error", (err) => {
  throw err;
});

archive.pipe(output);
archive.glob("**", { ignore }, { prefix: `${name}/` });

archive.finalize();
