class User {
  final int id;
  final String name;
  final String email;
  final bool isAdmin;
  final String? avatarUrl;

  User({
    required this.id,
    required this.name,
    required this.email,
    required this.isAdmin,
    this.avatarUrl,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String,
        isAdmin: json['is_admin'] == true,
        avatarUrl: json['avatar_url'] as String?,
      );
}
