/**
 * NextCloud App Build
 * 
 * @copyright Copyright (c) 2025 pdarleyjr <pdarleyjr@gmail.com>
 * @license MIT
 */

'use strict'

import app from '../../src/main'

describe('NextCloud App Build', () => {
  test('should have correct name', () => {
    expect(app.name).toBe('NextCloudAppBuild')
  })

  test('should have correct version', () => {
    expect(app.version).toBe('0.1.0')
  })

  test('should have init function', () => {
    expect(typeof app.init).toBe('function')
  })
})