class SportInfo {
  final String key;
  final String name;
  final String icon;
  final bool isTeam;
  final int playersPerSide;
  final String scoring;
  final bool allowsDraw;
  final int? maxSets;
  final int minutes;
  final bool usesPenalties;

  SportInfo({
    required this.key,
    required this.name,
    required this.icon,
    required this.isTeam,
    required this.playersPerSide,
    required this.scoring,
    required this.allowsDraw,
    required this.maxSets,
    required this.minutes,
    required this.usesPenalties,
  });

  factory SportInfo.fromJson(Map<String, dynamic> json) => SportInfo(
        key: json['key'] as String,
        name: json['name'] as String,
        icon: json['icon'] as String? ?? '🏆',
        isTeam: json['is_team'] as bool? ?? false,
        playersPerSide: json['players_per_side'] as int? ?? 1,
        scoring: json['scoring'] as String? ?? 'goals',
        allowsDraw: json['allows_draw'] as bool? ?? true,
        maxSets: json['max_sets'] as int?,
        minutes: json['minutes'] as int? ?? 6,
        usesPenalties: json['uses_penalties'] as bool? ?? false,
      );
}

class RuleDef {
  final String key;
  final String label;
  final String? labelEn;
  final String type;
  final String? defaultValue;
  final String group;
  final List<String> options;
  final int? min;
  final int? max;
  final String? note;
  final String? noteEn;

  RuleDef({
    required this.key,
    required this.label,
    this.labelEn,
    required this.type,
    this.defaultValue,
    this.group = 'general',
    this.options = const [],
    this.min,
    this.max,
    this.note,
    this.noteEn,
  });

  factory RuleDef.fromJson(Map<String, dynamic> json) => RuleDef(
        key: json['key'] as String,
        label: json['label'] as String? ?? '',
        labelEn: json['label_en'] as String?,
        type: json['type'] as String? ?? 'boolean',
        defaultValue: json['default']?.toString(),
        group: json['group'] as String? ?? 'general',
        options: (json['options'] as List?)?.map((e) => e.toString()).toList() ?? const [],
        min: json['min'] as int?,
        max: json['max'] as int?,
        note: json['note'] as String?,
        noteEn: json['note_en'] as String?,
      );
}

const Map<String, String> ruleOptLabels = {
  'ilimitado': 'Ilimitado',
  'sin_reloj': 'Sin reloj',
  'solo_saque_anota': 'Solo el saque anota',
  'rally_point': 'Rally point',
  'amateur': 'Amateur',
  'semi_pro': 'Semiprofesional',
  'pro': 'Profesional',
  'world_class': 'Clase mundial',
  'legendary': 'Leyenda',
};

const Map<String, String> ruleGroupLabels = {
  'general': 'Reglas generales',
  'tiempo': 'Tiempo',
  'sets': 'Sets',
  'marcador': 'Marcador',
  'desempate': 'Desempate',
};

class SportCatalog {
  final List<SportInfo> sports;
  final Map<String, List<RuleDef>> rules;

  SportCatalog({required this.sports, required this.rules});

  factory SportCatalog.fromJson(Map<String, dynamic> json) {
    final sports = (json['sports'] as List? ?? [])
        .map((e) => SportInfo.fromJson(e as Map<String, dynamic>))
        .toList();
    final rules = <String, List<RuleDef>>{};
    (json['rules'] as Map<String, dynamic>? ?? {}).forEach((sport, defs) {
      rules[sport] = (defs as List).map((d) => RuleDef.fromJson(d as Map<String, dynamic>)).toList();
    });
    return SportCatalog(sports: sports, rules: rules);
  }
}
