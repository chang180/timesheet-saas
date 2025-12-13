#!/usr/bin/env node

/**
 * ELK Stack MCP Server
 * 提供日誌查詢功能給 Cursor MCP 環境使用
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';

import http from 'http';

const ELASTICSEARCH_HOST = process.env.ELASTICSEARCH_HOST || 'http://localhost:9200';
const INDEX_PATTERN = process.env.ELASTICSEARCH_INDEX_PATTERN || 'laravel-logs-*';

class ElkMcpServer {
  constructor() {
    this.server = new Server(
      {
        name: 'elk-mcp-server',
        version: '1.0.0',
      },
      {
        capabilities: {
          tools: {},
        },
      }
    );

    this.setupHandlers();
    this.transport = new StdioServerTransport();
  }

  setupHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => ({
      tools: [
        {
          name: 'search_logs',
          description: '搜尋 Laravel 應用程式日誌。支援文字搜尋、日誌級別過濾、時間範圍查詢等功能。',
          inputSchema: {
            type: 'object',
            properties: {
              query: {
                type: 'string',
                description: '搜尋字串，會在訊息、例外訊息和堆疊追蹤中搜尋',
              },
              level: {
                type: 'string',
                enum: ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
                description: '日誌級別過濾',
              },
              from: {
                type: 'string',
                format: 'date-time',
                description: '開始時間 (ISO 8601 格式)',
              },
              to: {
                type: 'string',
                format: 'date-time',
                description: '結束時間 (ISO 8601 格式)',
              },
              limit: {
                type: 'integer',
                minimum: 1,
                maximum: 1000,
                default: 50,
                description: '返回結果數量限制',
              },
            },
          },
        },
        {
          name: 'filter_logs',
          description: '根據多個條件過濾日誌。支援按日誌級別、環境、用戶 ID、請求 ID 和時間範圍進行過濾。',
          inputSchema: {
            type: 'object',
            properties: {
              level: {
                type: 'string',
                enum: ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
                description: '日誌級別',
              },
              environment: {
                type: 'string',
                description: '環境名稱 (如 local, production, staging)',
              },
              user_id: {
                type: 'string',
                description: '用戶 ID',
              },
              request_id: {
                type: 'string',
                description: '請求 ID',
              },
              from: {
                type: 'string',
                format: 'date-time',
                description: '開始時間 (ISO 8601 格式)',
              },
              to: {
                type: 'string',
                format: 'date-time',
                description: '結束時間 (ISO 8601 格式)',
              },
              limit: {
                type: 'integer',
                minimum: 1,
                maximum: 1000,
                default: 50,
                description: '返回結果數量限制',
              },
            },
          },
        },
        {
          name: 'get_recent_errors',
          description: '取得最近的錯誤日誌。預設返回過去 24 小時內的 ERROR、CRITICAL、ALERT、EMERGENCY 級別的日誌。',
          inputSchema: {
            type: 'object',
            properties: {
              hours: {
                type: 'integer',
                minimum: 1,
                maximum: 168,
                default: 24,
                description: '查詢過去 N 小時的錯誤',
              },
              limit: {
                type: 'integer',
                minimum: 1,
                maximum: 1000,
                default: 50,
                description: '返回結果數量限制',
              },
            },
          },
        },
        {
          name: 'get_log_statistics',
          description: '取得日誌統計資料，包括各級別日誌數量、環境分布、時間分布等。',
          inputSchema: {
            type: 'object',
            properties: {
              from: {
                type: 'string',
                format: 'date-time',
                description: '開始時間 (ISO 8601 格式)',
              },
              to: {
                type: 'string',
                format: 'date-time',
                description: '結束時間 (ISO 8601 格式)',
              },
            },
          },
        },
      ],
    }));

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'search_logs':
            return await this.searchLogs(args);
          case 'filter_logs':
            return await this.filterLogs(args);
          case 'get_recent_errors':
            return await this.getRecentErrors(args);
          case 'get_log_statistics':
            return await this.getLogStatistics(args);
          default:
            throw new Error(`Unknown tool: ${name}`);
        }
      } catch (error) {
        return {
          content: [
            {
              type: 'text',
              text: `錯誤: ${error.message}`,
            },
          ],
          isError: true,
        };
      }
    });
  }

  async elasticsearchRequest(path, method = 'GET', body = null) {
    return new Promise((resolve, reject) => {
      const url = new URL(path, ELASTICSEARCH_HOST);
      const options = {
        method,
        headers: {
          'Content-Type': 'application/json',
        },
      };

      const req = http.request(url, options, (res) => {
        let data = '';
        res.on('data', (chunk) => {
          data += chunk;
        });
        res.on('end', () => {
          try {
            const json = JSON.parse(data);
            if (res.statusCode >= 200 && res.statusCode < 300) {
              resolve(json);
            } else {
              reject(new Error(`Elasticsearch error: ${json.error?.reason || data}`));
            }
          } catch (e) {
            reject(new Error(`Failed to parse response: ${data}`));
          }
        });
      });

      req.on('error', reject);

      if (body) {
        req.write(JSON.stringify(body));
      }

      req.end();
    });
  }

  async searchLogs(args) {
    const { query, level, from, to, limit = 50 } = args || {};

    const must = [];

    if (query) {
      must.push({
        multi_match: {
          query,
          fields: ['message', 'exception_message', 'stack_trace'],
          type: 'best_fields',
          fuzziness: 'AUTO',
        },
      });
    }

    if (level) {
      must.push({
        term: {
          'level.keyword': level.toLowerCase(),
        },
      });
    }

    if (from || to) {
      const range = {};
      if (from) range.gte = from;
      if (to) range.lte = to;
      must.push({
        range: {
          '@timestamp': range,
        },
      });
    }

    const esQuery = must.length === 0 ? { match_all: {} } : { bool: { must } };

    const result = await this.elasticsearchRequest(
      `/${INDEX_PATTERN}/_search`,
      'POST',
      {
        size: limit,
        sort: [{ '@timestamp': { order: 'desc' } }],
        query: esQuery,
      }
    );

    const logs = (result.hits?.hits || []).map((hit) => {
      const source = hit._source || {};
      return {
        id: hit._id,
        index: hit._index,
        timestamp: source['@timestamp'] || source.log_timestamp || null,
        level: source.level || 'unknown',
        environment: source.environment || 'unknown',
        message: source.message || '',
        exception_class: source.exception_class || null,
        exception_message: source.exception_message || null,
        user_id: source.user_id || null,
        request_id: source.request_id || null,
      };
    });

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(
            {
              total: result.hits?.total?.value || logs.length,
              logs,
            },
            null,
            2
          ),
        },
      ],
    };
  }

  async filterLogs(args) {
    const { level, environment, user_id, request_id, from, to, limit = 50 } = args || {};

    const must = [];

    if (level) {
      must.push({
        term: {
          'level.keyword': level.toLowerCase(),
        },
      });
    }

    if (environment) {
      must.push({
        term: {
          'environment.keyword': environment.toLowerCase(),
        },
      });
    }

    if (user_id) {
      must.push({
        term: {
          'user_id.keyword': String(user_id),
        },
      });
    }

    if (request_id) {
      must.push({
        term: {
          'request_id.keyword': String(request_id),
        },
      });
    }

    if (from || to) {
      const range = {};
      if (from) range.gte = from;
      if (to) range.lte = to;
      must.push({
        range: {
          '@timestamp': range,
        },
      });
    }

    const esQuery = must.length === 0 ? { match_all: {} } : { bool: { must } };

    const result = await this.elasticsearchRequest(
      `/${INDEX_PATTERN}/_search`,
      'POST',
      {
        size: limit,
        sort: [{ '@timestamp': { order: 'desc' } }],
        query: esQuery,
      }
    );

    const logs = (result.hits?.hits || []).map((hit) => {
      const source = hit._source || {};
      return {
        id: hit._id,
        index: hit._index,
        timestamp: source['@timestamp'] || source.log_timestamp || null,
        level: source.level || 'unknown',
        environment: source.environment || 'unknown',
        message: source.message || '',
        exception_class: source.exception_class || null,
        exception_message: source.exception_message || null,
        user_id: source.user_id || null,
        request_id: source.request_id || null,
      };
    });

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(
            {
              total: result.hits?.total?.value || logs.length,
              logs,
            },
            null,
            2
          ),
        },
      ],
    };
  }

  async getRecentErrors(args) {
    const { hours = 24, limit = 50 } = args || {};

    const fromTime = new Date(Date.now() - hours * 60 * 60 * 1000).toISOString();

    const must = [
      {
        range: {
          '@timestamp': {
            gte: fromTime,
          },
        },
      },
      {
        terms: {
          'level.keyword': ['error', 'critical', 'alert', 'emergency'],
        },
      },
    ];

    const result = await this.elasticsearchRequest(
      `/${INDEX_PATTERN}/_search`,
      'POST',
      {
        size: limit,
        sort: [{ '@timestamp': { order: 'desc' } }],
        query: { bool: { must } },
      }
    );

    const logs = (result.hits?.hits || []).map((hit) => {
      const source = hit._source || {};
      return {
        id: hit._id,
        index: hit._index,
        timestamp: source['@timestamp'] || source.log_timestamp || null,
        level: source.level || 'unknown',
        environment: source.environment || 'unknown',
        message: source.message || '',
        exception_class: source.exception_class || null,
        exception_message: source.exception_message || null,
        exception_file: source.exception_file || null,
        exception_line: source.exception_line || null,
        stack_trace: source.stack_trace || null,
        user_id: source.user_id || null,
        request_id: source.request_id || null,
      };
    });

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(
            {
              total: result.hits?.total?.value || logs.length,
              hours,
              logs,
            },
            null,
            2
          ),
        },
      ],
    };
  }

  async getLogStatistics(args) {
    const { from, to } = args || {};

    const query = {};
    if (from || to) {
      const range = {};
      if (from) range.gte = from;
      if (to) range.lte = to;
      query.range = { '@timestamp': range };
    }

    const esQuery = Object.keys(query).length === 0 ? { match_all: {} } : query;

    const result = await this.elasticsearchRequest(
      `/${INDEX_PATTERN}/_search`,
      'POST',
      {
        size: 0,
        query: esQuery,
        aggs: {
          levels: {
            terms: {
              field: 'level.keyword',
              size: 10,
            },
          },
          environments: {
            terms: {
              field: 'environment.keyword',
              size: 10,
            },
          },
          date_histogram: {
            date_histogram: {
              field: '@timestamp',
              calendar_interval: '1h',
            },
          },
        },
      }
    );

    const stats = {
      levels: {},
      environments: {},
      time_distribution: [],
    };

    if (result.aggregations?.levels?.buckets) {
      result.aggregations.levels.buckets.forEach((bucket) => {
        stats.levels[bucket.key] = bucket.doc_count;
      });
    }

    if (result.aggregations?.environments?.buckets) {
      result.aggregations.environments.buckets.forEach((bucket) => {
        stats.environments[bucket.key] = bucket.doc_count;
      });
    }

    if (result.aggregations?.date_histogram?.buckets) {
      result.aggregations.date_histogram.buckets.forEach((bucket) => {
        stats.time_distribution.push({
          time: bucket.key_as_string,
          count: bucket.doc_count,
        });
      });
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify(stats, null, 2),
        },
      ],
    };
  }

  async run() {
    await this.server.connect(this.transport);
    console.error('ELK MCP Server running on stdio');
  }
}

const server = new ElkMcpServer();
server.run().catch(console.error);
