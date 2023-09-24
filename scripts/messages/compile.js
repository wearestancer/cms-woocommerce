const exec = require("node:child_process").exec;
const path = require("node:path");
const glob = require("glob");

glob("languages/*.po", (err, files) => {
  if (err) {
    throw err;
  }

  files.forEach((file) => {
    const portable = path.join(process.cwd(), file);
    const message = portable.replace(/\.po$/, '.mo');

    exec(`msgfmt ${portable} -o ${message}`, (error) => {
      if (error) {
        throw error;
      }
    });
  });
});
