module.exports = {
  testEnvironment: 'jsdom',
  moduleFileExtensions: [
    'js',
    'json'
  ],
  transform: {
    '^.+\\.js$': 'babel-jest'
  },
  transformIgnorePatterns: [
    '/node_modules/'
  ],
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1'
  },
  testMatch: [
    '**/tests/js/**/*.spec.js'
  ],
  collectCoverage: true,
  collectCoverageFrom: [
    'src/**/*.js',
    '!src/**/*.spec.js'
  ],
  coverageReporters: [
    'text',
    'lcov'
  ],
  coverageDirectory: 'coverage'
}