import 'package:flutter/material.dart';

/// Collapsible Material 3 card every console panel lives in.
class DevPanel extends StatelessWidget {
  final String title;
  final IconData icon;
  final List<Widget> children;
  final bool initiallyExpanded;

  const DevPanel({
    super.key,
    required this.title,
    required this.icon,
    required this.children,
    this.initiallyExpanded = false,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      clipBehavior: Clip.antiAlias,
      child: ExpansionTile(
        leading: Icon(icon, size: 20),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        initiallyExpanded: initiallyExpanded,
        childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
        children: children,
      ),
    );
  }
}

/// One label/value row.
class KV extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;
  const KV(this.label, this.value, {super.key, this.valueColor});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 150,
            child: Text(label,
                style: Theme.of(context)
                    .textTheme
                    .bodySmall
                    ?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
          ),
          Expanded(
            child: Text(value,
                style: Theme.of(context)
                    .textTheme
                    .bodySmall
                    ?.copyWith(color: valueColor, fontFamily: 'monospace')),
          ),
        ],
      ),
    );
  }
}

/// The honest "this service doesn't exist yet" row — used instead of
/// fake values, per the console's ground rules.
class NotAvailable extends StatelessWidget {
  final String label;
  final String? reason;
  const NotAvailable(this.label, {super.key, this.reason});

  @override
  Widget build(BuildContext context) {
    return KV(label, reason == null ? 'Not Available' : 'Not Available — $reason',
        valueColor: Theme.of(context).colorScheme.onSurfaceVariant);
  }
}

/// Green/red/grey status indicator with label.
class StatusRow extends StatelessWidget {
  final String label;
  final bool? ok; // null = unknown/unavailable
  final String? detail;
  const StatusRow(this.label, this.ok, {super.key, this.detail});

  @override
  Widget build(BuildContext context) {
    final color = ok == null
        ? Theme.of(context).colorScheme.onSurfaceVariant
        : ok!
            ? Colors.greenAccent
            : Colors.redAccent;
    final text = ok == null ? 'ukjent' : (ok! ? 'OK' : 'FEIL');
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        children: [
          Icon(Icons.circle, size: 10, color: color),
          const SizedBox(width: 8),
          SizedBox(width: 142, child: Text(label, style: Theme.of(context).textTheme.bodySmall)),
          Expanded(
            child: Text(detail ?? text,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: color)),
          ),
        ],
      ),
    );
  }
}
