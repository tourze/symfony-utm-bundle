# Symfony UTM Bundle 工作流程

本文档描述了Symfony UTM Bundle中各个核心功能的工作流程，包括UTM参数捕获、会话管理、转化跟踪和用户路径分析等。

## 1. UTM参数捕获工作流

当用户通过带有UTM参数的链接访问应用程序时，此工作流负责捕获、验证和存储这些参数。

```mermaid
sequenceDiagram
    participant User
    participant RequestListener as UTM请求监听器
    participant ParametersExtractor as UTM参数提取服务
    participant ParametersValidator as UTM参数验证服务
    participant SessionManager as UTM会话管理器
    participant StorageStrategy as 存储策略
    participant Database as 数据库

    User->>RequestListener: 访问带UTM参数的URL
    RequestListener->>ParametersExtractor: 提取UTM参数
    ParametersExtractor->>RequestListener: 返回原始UTM参数
    RequestListener->>ParametersValidator: 验证UTM参数
    ParametersValidator->>RequestListener: 返回验证后的UTM参数
    RequestListener->>SessionManager: 创建/更新UTM会话
    SessionManager->>StorageStrategy: 存储UTM参数
    alt 使用数据库存储
        StorageStrategy->>Database: 创建UtmParameters实体
        StorageStrategy->>Database: 创建/更新UtmSession实体
    else 使用会话存储
        StorageStrategy->>SessionManager: 存储到用户会话
    end
    SessionManager->>RequestListener: 会话更新完成
    RequestListener->>User: 继续请求处理
```

### 关键组件

1. **UTM请求监听器**: 
   - 实现: `Symfony\Component\HttpKernel\EventListener\RequestListener`
   - 职责: 监听所有HTTP请求，检测UTM参数

2. **UTM参数提取服务**:
   - 职责: 从HTTP请求中提取标准和自定义UTM参数

3. **UTM参数验证服务**:
   - 职责: 验证UTM参数，过滤无效值，标准化数据

4. **UTM会话管理器**:
   - 职责: 管理UTM会话生命周期，处理会话合并和过期

5. **存储策略**:
   - 接口: `UtmStorageStrategyInterface`
   - 具体实现:
     - `SessionStorageStrategy`: 存储在用户会话中
     - `CookieStorageStrategy`: 存储在Cookie中
     - `DatabaseStorageStrategy`: 持久化到数据库

### 实体变化

- **UtmParameters**: 创建新实体记录UTM参数值
- **UtmSession**: 创建或更新会话实体，关联UTM参数

## 2. UTM会话关联与用户识别工作流

此工作流描述了如何将UTM会话与已登录的用户关联起来，以及处理跨设备识别。

```mermaid
sequenceDiagram
    participant User
    participant SecurityListener as 安全监听器
    participant SessionManager as UTM会话管理器
    participant UserProvider as 用户提供者
    participant StorageStrategy as 存储策略
    participant Database as 数据库

    User->>SecurityListener: 用户登录
    SecurityListener->>UserProvider: 获取用户信息
    UserProvider->>SecurityListener: 返回用户
    SecurityListener->>SessionManager: 关联用户与UTM会话
    SessionManager->>StorageStrategy: 查询现有UTM会话
    StorageStrategy->>SessionManager: 返回UTM会话
    alt 存在匿名UTM会话
        SessionManager->>Database: 更新UtmSession.userIdentifier
    else 不存在UTM会话
        SessionManager->>Database: 创建新UtmSession并关联用户
    end
    SessionManager->>SecurityListener: 会话关联完成
    SecurityListener->>User: 继续处理
```

### 关键组件

1. **安全监听器**:
   - 实现: `Symfony\Component\Security\Http\Firewall\AuthenticationListener`
   - 职责: 监听用户登录事件

2. **UTM会话管理器**:
   - 职责: 管理UTM会话与用户的关联

3. **用户提供者**:
   - 职责: 提供用户信息和标识符

### 实体变化

- **UtmSession**: 更新userIdentifier字段，建立与用户的关联

## 3. 转化事件跟踪工作流

当用户完成重要操作（如注册、购买等）时，此工作流负责跟踪这些转化事件并关联UTM数据。

```mermaid
flowchart TD
    A[应用程序] -->|触发转化事件| B[转化事件监听器]
    B -->|查询当前UTM数据| C[UTM上下文管理器]
    C -->|返回UTM会话和参数| B
    B -->|创建转化记录| D[转化服务]
    D -->|存储转化| E[(数据库)]
    B -->|派发转化事件| F[事件调度器]
    F -->|UtmConversionEvent| G[自定义监听器]
    E -->|创建| H[UtmConversion实体]
    
    subgraph 转化分析
    I[分析服务] -->|查询转化数据| E
    I -->|生成报表| J[报表数据]
    end
```

### 关键组件

1. **转化事件监听器**:
   - 职责: 监听应用程序中的转化事件

2. **UTM上下文管理器**:
   - 职责: 提供当前请求的UTM上下文信息

3. **转化服务**:
   - 职责: 创建和存储转化记录
   - 方法: `trackConversion(string $eventName, ?float $value = null, array $metadata = [])`

### 实体变化

- **UtmConversion**: 创建新实体，关联UTM参数和会话

## 4. 用户路径跟踪工作流

此工作流跟踪用户在网站上的导航路径，记录页面访问和行为序列。

```mermaid
sequenceDiagram
    participant User
    participant Controller as 控制器
    participant PathTracker as 路径跟踪服务
    participant ContextManager as UTM上下文管理器
    participant Database as 数据库
    
    User->>Controller: 访问页面
    Controller->>PathTracker: 记录页面访问
    PathTracker->>ContextManager: 获取当前UTM上下文
    ContextManager->>PathTracker: 返回UTM会话
    alt 可跟踪页面
        PathTracker->>Database: 创建UtmUserPath记录
        PathTracker->>Database: 更新路径序列
    else 不可跟踪页面
        PathTracker->>Controller: 忽略跟踪
    end
    PathTracker->>Controller: 跟踪完成
    Controller->>User: 返回响应
```

### 关键组件

1. **路径跟踪服务**:
   - 职责: 跟踪和记录用户导航路径
   - 方法: `trackPageView(string $pageUrl, array $metadata = [])`

2. **UTM上下文管理器**:
   - 职责: 提供当前请求的UTM上下文信息

### 实体变化

- **UtmUserPath**: 创建新实体，记录页面访问和步骤序号

## 5. 漏斗分析工作流

此工作流创建和分析转化漏斗，跟踪用户通过预定义步骤的进度。

```mermaid
flowchart TD
    A[漏斗定义] -->|创建| B[漏斗管理服务]
    B -->|存储| C[(数据库)]
    C -->|创建| D[UtmFunnel实体]
    B -->|定义步骤| E[漏斗步骤服务]
    E -->|存储| C
    C -->|创建| F[UtmFunnelStep实体]
    
    G[用户动作] -->|触发| H[事件监听器]
    H -->|检查漏斗步骤匹配| I[漏斗分析服务]
    I -->|查询漏斗配置| C
    I -->|记录漏斗转化| C
    C -->|创建| J[UtmFunnelConversion实体]
    
    K[分析请求] -->|查询| L[漏斗报表服务]
    L -->|检索数据| C
    L -->|生成漏斗分析| M[漏斗报表数据]
```

### 关键组件

1. **漏斗管理服务**:
   - 职责: 创建和管理漏斗定义
   - 方法: `createFunnel(string $name, string $description, array $steps)`

2. **漏斗分析服务**:
   - 职责: 分析用户在漏斗中的进度
   - 方法: `analyzeFunnel(string $funnelId, ?string $startDate = null, ?string $endDate = null)`

### 实体变化

- **UtmFunnel**: 创建漏斗定义
- **UtmFunnelStep**: 创建漏斗步骤
- **UtmFunnelConversion**: 记录用户完成漏斗步骤的事件

## 6. 用户分群工作流

此工作流创建用户分群并基于UTM数据和用户行为对用户进行分组。

```mermaid
flowchart TD
    A[分群定义] -->|创建| B[分群管理服务]
    B -->|存储| C[(数据库)]
    C -->|创建| D[UtmSegment实体]
    B -->|定义规则| E[分群规则服务]
    E -->|存储| C
    C -->|创建| F[UtmSegmentRule实体]
    
    G[分群处理任务] -->|执行| H[分群处理服务]
    H -->|查询规则| C
    H -->|查询用户和UTM数据| C
    H -->|计算分群成员| I[分群计算引擎]
    I -->|存储分群成员| C
    C -->|创建| J[UtmSegmentMembership实体]
    
    K[查询分群] -->|获取成员| L[分群查询服务]
    L -->|检索数据| C
    L -->|返回分群数据| M[分群成员列表]
```

### 关键组件

1. **分群管理服务**:
   - 职责: 创建和管理用户分群
   - 方法: `createSegment(string $name, string $description, array $rules)`

2. **分群处理服务**:
   - 职责: 计算分群成员资格
   - 方法: `processSegment(string $segmentId)`

3. **分群计算引擎**:
   - 职责: 基于规则评估用户资格

### 实体变化

- **UtmSegment**: 创建分群定义
- **UtmSegmentRule**: 创建分群规则
- **UtmSegmentMembership**: 记录用户的分群成员资格

## 7. UTM链接生成工作流

此工作流创建并管理带有UTM参数的链接，支持短链接生成和点击跟踪。

```mermaid
sequenceDiagram
    participant User
    participant LinkBuilder as 链接构建器
    participant CampaignService as 活动服务
    participant ShortenerService as 短链接服务
    participant Database as 数据库
    
    User->>LinkBuilder: 请求创建UTM链接
    LinkBuilder->>CampaignService: 获取活动信息
    CampaignService->>Database: 查询UtmCampaign
    Database->>CampaignService: 返回活动
    CampaignService->>LinkBuilder: 返回活动信息
    LinkBuilder->>LinkBuilder: 构建UTM参数
    LinkBuilder->>ShortenerService: 生成短链接
    ShortenerService->>LinkBuilder: 返回短链接
    LinkBuilder->>Database: 存储UtmLink实体
    LinkBuilder->>User: 返回UTM链接
    
    User->>ShortenerService: 点击短链接
    ShortenerService->>Database: 更新点击计数
    ShortenerService->>User: 重定向到目标URL（带UTM参数）
```

### 关键组件

1. **链接构建器**:
   - 职责: 创建带有UTM参数的链接
   - 方法: `buildLink(string $baseUrl, array $utmParams, ?string $campaignId = null)`

2. **短链接服务**:
   - 职责: 生成和管理短链接
   - 方法: `shorten(string $url): string`

### 实体变化

- **UtmParameters**: 创建UTM参数记录
- **UtmLink**: 创建链接记录，关联UTM参数和活动

## 8. 归因分析工作流

此工作流分析转化事件与UTM数据的关系，执行归因分析。

```mermaid
flowchart TD
    A[分析请求] -->|触发| B[归因分析服务]
    B -->|查询转化数据| C[(数据库)]
    B -->|查询UTM参数和会话| C
    B -->|应用归因模型| D[归因模型引擎]
    D -->|首次接触归因| E[首次接触结果]
    D -->|最后接触归因| F[最后接触结果]
    D -->|线性归因| G[线性归因结果]
    D -->|位置归因| H[位置归因结果]
    D -->|时间衰减归因| I[时间衰减结果]
    D -->|自定义归因| J[自定义归因结果]
    B -->|汇总结果| K[归因报表]
```

### 关键组件

1. **归因分析服务**:
   - 职责: 执行不同类型的归因分析
   - 方法: `analyze(string $conversionType, string $attributionModel, ?string $startDate = null, ?string $endDate = null)`

2. **归因模型引擎**:
   - 职责: 实现不同的归因算法
   - 支持模型: 首次接触、最后接触、线性、位置、时间衰减等

## 9. 整体数据流

下图展示了UTM数据在系统中的整体流动过程，从捕获到分析的完整链路。

```mermaid
flowchart TD
    A[用户访问] -->|带UTM参数| B[参数捕获]
    B -->|创建| C[UtmParameters实体]
    B -->|创建/更新| D[UtmSession实体]
    
    E[用户登录] -->|关联用户| D
    
    F[页面浏览] -->|记录| G[UtmUserPath实体]
    G -->|关联| D
    
    H[转化事件] -->|创建| I[UtmConversion实体]
    I -->|关联| C
    I -->|关联| D
    
    J[漏斗定义] -->|创建| K[UtmFunnel实体]
    K -->|包含| L[UtmFunnelStep实体]
    H -->|记录| M[UtmFunnelConversion实体]
    M -->|关联| K
    M -->|关联| L
    M -->|关联| D
    
    N[分群定义] -->|创建| O[UtmSegment实体]
    O -->|包含| P[UtmSegmentRule实体]
    E -->|计算成员资格| Q[UtmSegmentMembership实体]
    Q -->|关联| O
    
    R[营销活动] -->|创建| S[UtmCampaign实体]
    S -->|生成| T[UtmLink实体]
    T -->|使用| C
    
    C -->|分析| U[UTM参数分析]
    I -->|分析| V[转化分析]
    G -->|分析| W[用户路径分析]
    M -->|分析| X[漏斗分析]
    Q -->|分析| Y[分群分析]
    T -->|分析| Z[链接性能分析]
    
    U -->|输出| AA[营销渠道报表]
    V -->|输出| AB[转化归因报表]
    W -->|输出| AC[用户行为报表]
    X -->|输出| AD[漏斗转化报表]
    Y -->|输出| AE[分群洞察报表]
    Z -->|输出| AF[链接效果报表]
```

## 10. 服务依赖关系

以下图表展示了UTM Bundle中各个服务之间的依赖关系。

```mermaid
classDiagram
    UtmParametersExtractor <-- UtmRequestListener
    UtmParametersValidator <-- UtmRequestListener
    UtmSessionManager <-- UtmRequestListener
    UtmStorageStrategyInterface <-- UtmSessionManager
    UtmContextManager <-- UtmSessionManager
    UtmContextManager <-- UtmConversionTracker
    UtmContextManager <-- UtmPathTracker
    UtmSessionManager <-- UtmSecurityListener
    UtmConversionTracker <-- ConversionEventListener
    UtmFunnelManager <-- UtmFunnelAnalyzer
    UtmSegmentManager <-- UtmSegmentProcessor
    UtmLinkBuilder <-- UtmCampaignManager
    UtmAttributionAnalyzer <-- UtmReportGenerator
    UtmPathAnalyzer <-- UtmReportGenerator
    UtmFunnelAnalyzer <-- UtmReportGenerator
    UtmSegmentAnalyzer <-- UtmReportGenerator
    
    class UtmParametersExtractor {
        +extract(Request): array
    }
    class UtmParametersValidator {
        +validate(array): array
    }
    class UtmSessionManager {
        +createSession(UtmParameters): UtmSession
        +getSession(): ?UtmSession
        +associateUser(string): void
    }
    class UtmStorageStrategyInterface {
        <<interface>>
        +store(UtmParameters): void
        +retrieve(): ?UtmParameters
        +clear(): void
    }
    class UtmContextManager {
        +getCurrentParameters(): ?UtmParameters
        +getCurrentSession(): ?UtmSession
    }
    class UtmConversionTracker {
        +trackConversion(string, float, array): UtmConversion
    }
    class UtmPathTracker {
        +trackPageView(string, array): UtmUserPath
    }
    class UtmFunnelManager {
        +createFunnel(string, string, array): UtmFunnel
        +addStep(UtmFunnel, string, string, int): UtmFunnelStep
    }
    class UtmFunnelAnalyzer {
        +analyzeFunnel(string, ?string, ?string): array
    }
    class UtmSegmentManager {
        +createSegment(string, string, array): UtmSegment
        +addRule(UtmSegment, string, string, mixed): UtmSegmentRule
    }
    class UtmSegmentProcessor {
        +processSegment(string): void
    }
    class UtmLinkBuilder {
        +buildLink(string, array, ?string): UtmLink
    }
    class UtmCampaignManager {
        +createCampaign(string, string, string): UtmCampaign
    }
    class UtmAttributionAnalyzer {
        +analyze(string, string, ?string, ?string): array
    }
    class UtmReportGenerator {
        +generateChannelReport(?string, ?string): array
        +generateAttributionReport(string, ?string, ?string): array
        +generatePathReport(?string, ?string): array
        +generateFunnelReport(string, ?string, ?string): array
        +generateSegmentReport(string): array
    }
    class UtmRequestListener {
        +onKernelRequest(RequestEvent): void
    }
    class UtmSecurityListener {
        +onLoginSuccess(LoginSuccessEvent): void
    }
    class ConversionEventListener {
        +onConversionEvent(ConversionEvent): void
    }
```

## 配置示例

```yaml
symfony_utm:
    # 存储配置
    storage:
        strategy: database  # 可选: session, cookie, database
        session_key: utm_parameters
        cookie_lifetime: 2592000  # 30天，单位：秒
    
    # 参数配置
    parameters:
        # 允许捕获的标准UTM参数
        allowed_parameters:
            - utm_source
            - utm_medium
            - utm_campaign
            - utm_term
            - utm_content
        # 允许捕获的自定义UTM参数（可选）
        custom_parameters:
            - utm_affiliate
            - utm_partner
        # 验证规则
        validation:
            max_length: 255
            sanitize: true
    
    # 会话配置
    session:
        lifetime: 2592000  # 30天，单位：秒
        renew_on_visit: true
    
    # 路径跟踪配置
    path_tracking:
        enabled: true
        exclude_paths:
            - /admin/*
            - /api/*
            - /_profiler/*
        max_paths_per_session: 100
    
    # 漏斗配置
    funnel:
        max_steps: 10
        conversion_window: 2592000  # 30天，单位：秒
    
    # 分群配置
    segment:
        processing_interval: 86400  # 1天，单位：秒
        membership_lifetime: 2592000  # 30天，单位：秒
    
    # 链接生成配置
    link_builder:
        enable_shortener: true
        shortener_service: 'app.link_shortener'  # 服务ID（可选）
    
    # 报表配置
    reporting:
        default_attribution_model: last_touch  # 可选: first_touch, last_touch, linear, position, time_decay
        date_format: 'Y-m-d'
``` 