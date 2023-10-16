const fs = require("node:fs");
const exec = require("node:child_process").exec;
const path = require("node:path");
const glob = require("glob");

const template = fs.realpathSync("languages/stancer.pot");

glob("languages/*.po", (err, files) => {
  if (err) {
    throw err;
  }

  files.forEach((file) => {
    const portable = path.join(process.cwd(), file);

    exec(`msgmerge ${portable} ${template} --update --backup=off`, (error) => {
      if (error) {
        throw error;
      }
    });
  });
});
