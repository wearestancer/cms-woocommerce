module.exports = {
  plugins: [
    'preset-default',
    {
      name: 'cleanupIDs',
      params: {
        minify: true,
      },
    },
    {
      name: 'removeDimensions',
    },
    {
      active: false,
      name: 'removeViewBox',
    },
  ],
};
