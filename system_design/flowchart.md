flowchart TD
    %% Entry Point
    Start([User Visits EcoComm]) --> AuthCheck{Already<br/>Authenticated?}
    
    %% Authentication Check
    AuthCheck -->|Yes| Dashboard[Dashboard<br/>dashboard.php]
    AuthCheck -->|No| AuthChoice{New User or<br/>Existing User?}
    
    %% Registration Flow
    AuthChoice -->|New User| SignupPage[Registration Page<br/>signup.html]
    SignupPage --> RoleSelection[Select Ecosystem Role<br/>• Community Member<br/>• Active Contributor<br/>• Project Coordinator<br/>• Ecosystem Observer]
    RoleSelection --> SignupProcess[Registration Processing<br/>signup.php]
    SignupProcess --> ValidationCheck{Input Valid?}
    ValidationCheck -->|No| SignupPage
    ValidationCheck -->|Yes| CreateAccount[Create Account<br/>• Hash Password<br/>• Assign Role<br/>• Create Session]
    CreateAccount --> Dashboard
    
    %% Login Flow
    AuthChoice -->|Existing User| LoginPage[Login Page<br/>login.html]
    LoginPage --> LoginProcess[Login Processing<br/>login.php]
    LoginProcess --> CredentialCheck{Credentials<br/>Valid?}
    CredentialCheck -->|No| FailedAttempt[Failed Attempt<br/>• Log Security Event<br/>• Increment Counter]
    FailedAttempt --> LockoutCheck{5 Failed<br/>Attempts?}
    LockoutCheck -->|Yes| AccountLocked[Account Locked<br/>Security Alert]
    LockoutCheck -->|No| LoginPage
    AccountLocked --> LoginPage
    
    %% Successful Login
    CredentialCheck -->|Yes| SessionCreate[Create Session<br/>• Generate Session ID<br/>• Store in Database<br/>• Set Cookies]
    SessionCreate --> LoadPermissions[Load User Permissions<br/>• Get Role<br/>• Get Permissions<br/>• Store in Session]
    LoadPermissions --> Dashboard
    
    %% Dashboard Role-Based Access
    Dashboard --> RoleCheck{User Role?}
    RoleCheck -->|Ecosystem Guardian| SuperAdminDash[Super Admin Dashboard<br/>• Full System Access<br/>• User Management<br/>• System Settings]
    RoleCheck -->|Ecosystem Manager| AdminDash[Admin Dashboard<br/>• Administrative Access<br/>• Content Management<br/>• User Oversight]
    RoleCheck -->|Community Steward| ModDash[Moderator Dashboard<br/>• Content Moderation<br/>• Community Management]
    RoleCheck -->|Project Coordinator| ProjectDash[Project Dashboard<br/>• Project Management<br/>• Team Coordination]
    RoleCheck -->|Active Contributor| ContribDash[Contributor Dashboard<br/>• Content Creation<br/>• Community Participation]
    RoleCheck -->|Community Member| MemberDash[Member Dashboard<br/>• Basic Access<br/>• Community Features]
    RoleCheck -->|Ecosystem Observer| ObserverDash[Observer Dashboard<br/>• Read-Only Access<br/>• View Content]
    
    %% Common Dashboard Features
    SuperAdminDash --> CommonFeatures[Common Features]
    AdminDash --> CommonFeatures
    ModDash --> CommonFeatures
    ProjectDash --> CommonFeatures
    ContribDash --> CommonFeatures
    MemberDash --> CommonFeatures
    ObserverDash --> CommonFeatures
    
    %% Profile Management
    CommonFeatures --> ProfileMgmt[Profile Management<br/>profile.php]
    ProfileMgmt --> UpdateProfile[Update Profile<br/>• Personal Info<br/>• Password Change<br/>• Preferences]
    UpdateProfile --> Dashboard
    
    %% Permission Checking
    CommonFeatures --> PermissionCheck[Permission Check<br/>hasPermission]
    PermissionCheck --> FeatureAccess{Has Permission?}
    FeatureAccess -->|Yes| AllowedFeature[Access Granted<br/>• Show Feature<br/>• Log Activity]
    FeatureAccess -->|No| DeniedFeature[Access Denied<br/>• Hide Feature<br/>• Log Attempt]
    AllowedFeature --> CommonFeatures
    DeniedFeature --> CommonFeatures
    
    %% Session Management
    Dashboard --> SessionCheck[Session Validation<br/>• Check Expiry<br/>• Verify Database<br/>• Update Activity]
    SessionCheck --> SessionValid{Session Valid?}
    SessionValid -->|No| ForceLogout[Force Logout<br/>• Clear Session<br/>• Redirect to Login]
    SessionValid -->|Yes| Dashboard
    ForceLogout --> LoginPage
    
    %% Logout Flow
    CommonFeatures --> LogoutOption[Logout<br/>logout.php]
    LogoutOption --> LogoutProcess[Logout Processing<br/>• Destroy Session<br/>• Clear Cookies<br/>• Log Activity]
    LogoutProcess --> LoginPage
    
    %% Database Operations
    SignupProcess --> UserDB[(Users Database<br/>• users<br/>• roles<br/>• permissions<br/>• user_roles<br/>• role_permissions)]
    LoginProcess --> UserDB
    SessionCreate --> SessionDB[(Session Database<br/>• user_sessions)]
    LoadPermissions --> UserDB
    ProfileMgmt --> UserDB
    AllowedFeature --> ActivityDB[(Activity Logs<br/>• user_activity_logs<br/>• security_logs)]
    DeniedFeature --> ActivityDB
    FailedAttempt --> ActivityDB
    
    %% Security Features
    LoginProcess --> SecurityFeatures[Security Features<br/>• Input Sanitization<br/>• CSRF Protection<br/>• Password Hashing<br/>• Rate Limiting]
    SecurityFeatures --> UserDB
    
    %% Styling
    classDef entryPoint fill:#e8f5e8
    classDef authFlow fill:#e1f5fe
    classDef roleSystem fill:#f3e5f5
    classDef dashboard fill:#fff3e0
    classDef security fill:#ffebee
    classDef database fill:#f5f5f5
    classDef permission fill:#e0f2f1
    
    class Start,AuthCheck,AuthChoice entryPoint
    class SignupPage,SignupProcess,LoginPage,LoginProcess,SessionCreate,LogoutOption,LogoutProcess authFlow
    class RoleSelection,RoleCheck,SuperAdminDash,AdminDash,ModDash,ProjectDash,ContribDash,MemberDash,ObserverDash roleSystem
    class Dashboard,CommonFeatures,ProfileMgmt,UpdateProfile dashboard
    class SecurityFeatures,FailedAttempt,AccountLocked,SessionCheck,ForceLogout security
    class UserDB,SessionDB,ActivityDB database
    class PermissionCheck,FeatureAccess,AllowedFeature,DeniedFeature permission