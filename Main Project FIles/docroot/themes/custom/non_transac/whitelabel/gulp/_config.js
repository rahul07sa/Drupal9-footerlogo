module.exports = {
  rtl: true,
  styles: {
    source: [
      'sass/**/*.scss',
      'components/**/*.scss'
    ],
    directionalSource: [
      'sass/**/*.ltr.scss',
      'components/**/*.ltr.scss'
    ],
    destination: 'components/dist/css/',
    options: {
      outputStyle: 'compressed',
      includePaths: [
        './node_modules'
      ]
    },
    variables: {
      $direction: 'rtl'
    }
  },
  scripts: {
    source: [
      'js/**/*.js',
      'components/js/**/*.js',
      'components/_patterns/**/*.js'
    ],
    destination: 'components/dist/js/'
  },
  svg: {
    source: 'images/icons/svg/**/*.svg',
    destination: 'dist/images/icons/svg/'
  },
  env: {
    prod: 'production',
    test: 'test',
    dev: 'development'
  },
  autoprefixer: {
  },
  stylelint: {
    options: {
      reporters: [
        {
          formatter: 'string',
          console: true
        }
      ]
    },
    optionsTest: {
      reporters: [
        {
          formatter: 'string',
          console: true,
          failAfterError: true
        }
      ]
    }
  },
  eslint: {
    options: {
      fix: false
    }
  },
  babel: {
    presets: [[
      'env', {
        targets: {
          browsers: ['last 2 versions']
        }
      }
    ]]
  },
  browserSync: {
    proxy: 'https://local.non-transac.com',
    open: true,
    xip: false,
    logConnections: false
  },
  patternlab: {
    command: 'php patternlab/core/console --generate',
    source: [
      'components/**/*.twig',
      'components/**/*.yml'
    ]
  }
};
